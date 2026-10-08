<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

use JsonFicator\Attribute\JsonFicatorDescription;
use JsonFicator\Attribute\JsonFicatorOptional;
use JsonFicator\Attribute\JsonFicatorRequired;
use JsonFicator\Exception\SchemaException;
use JsonFicator\IJsonFicatorModel;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Resolves a PHP class into the internal schema using reflection.
 *
 * Supported property types:
 * - primitives: string, int, float, bool
 * - nullable primitives: ?string, ?int, ...
 * - arrays: array, list, array<string>, etc. (any array shape)
 * - nested models: any class implementing {@see IJsonFicatorModel}
 *
 * Required/optional semantics (see ARCHITECTURE.md for the full rationale):
 * - A non-nullable property is REQUIRED by default.
 * - A nullable property is OPTIONAL by default.
 * - `#[JsonFicatorRequired]` forces a property to be required.
 * - `#[JsonFicatorOptional]` forces a property to be optional.
 *
 * Only public properties are considered. Static and readonly properties are
 * skipped.
 */
final class ClassSchemaResolver implements SchemaResolver
{
    /**
     * @param bool $requireInterface Whether the class must implement IJsonFicatorModel.
     */
    public function __construct(
        private readonly bool $requireInterface = true,
    ) {
    }

    public function resolve(mixed $definition): JsonFicatorSchema
    {
        if (!is_string($definition) || !class_exists($definition)) {
            throw new SchemaException(
                'Class schema must be a valid class name, got: ' . var_export($definition, true)
            );
        }

        $class = $definition;

        if ($this->requireInterface && !is_subclass_of($class, IJsonFicatorModel::class)) {
            throw new SchemaException(
                "Class '{$class}' must implement " . IJsonFicatorModel::class . '.'
            );
        }

        $reflection = new ReflectionClass($class);
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $fields[$property->getName()] = $this->resolveProperty($property);
        }

        return new JsonFicatorSchema($fields, $class);
    }

    private function resolveProperty(ReflectionProperty $property): SchemaField
    {
        $name = $property->getName();
        $type = $property->getType();

        if ($type === null) {
            throw new SchemaException(
                "Property '{$name}' in class '{$property->getDeclaringClass()->getName()}' has no type. "
                . 'Typed properties are required.'
            );
        }

        $description = $this->readDescription($property);
        $required = $this->resolveRequired($property);

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnion($name, $type, $required, $description);
        }

        if (!($type instanceof ReflectionNamedType)) {
            throw new SchemaException(
                "Property '{$name}' has an unsupported type: " . (string) $type
            );
        }

        return $this->resolveNamed($name, $type, $required, $description);
    }

    private function resolveNamed(
        string $name,
        ReflectionNamedType $type,
        bool $required,
        ?string $description,
    ): SchemaField {
        $nullable = $type->allowsNull();
        $typeName = $type->getName();

        // array / list
        if ($typeName === 'array' || $typeName === 'list') {
            return new SchemaField(
                name: $name,
                type: 'array',
                nullable: $nullable,
                required: $required,
                description: $description,
            );
        }

        // primitive
        if (in_array($typeName, ['string', 'int', 'float', 'bool'], true)) {
            return new SchemaField(
                name: $name,
                type: $typeName,
                nullable: $nullable,
                required: $required,
                description: $description,
            );
        }

        // nested model
        if (class_exists($typeName)) {
            return new SchemaField(
                name: $name,
                type: 'object',
                nullable: $nullable,
                required: $required,
                description: $description,
                properties: $this->resolveNested($typeName),
                class: $typeName,
            );
        }

        throw new SchemaException(
            "Property '{$name}' has an unsupported type '{$typeName}'. "
            . 'Supported: string, int, float, bool, array, list, and classes implementing '
            . IJsonFicatorModel::class . '.'
        );
    }

    private function resolveUnion(
        string $name,
        ReflectionUnionType $type,
        bool $required,
        ?string $description,
    ): SchemaField {
        $types = $type->getTypes();

        // Only "null | X" unions are supported.
        $nonNull = array_values(array_filter(
            $types,
            static fn ($t): bool => !($t instanceof ReflectionNamedType && $t->getName() === 'null'),
        ));

        if (count($nonNull) !== 1) {
            throw new SchemaException(
                "Property '{$name}' has an unsupported union type: " . (string) $type
                . '. Only "null | <type>" unions are supported.'
            );
        }

        $inner = $nonNull[0];
        if (!($inner instanceof ReflectionNamedType)) {
            throw new SchemaException(
                "Property '{$name}' has an unsupported union type: " . (string) $type
            );
        }

        $field = $this->resolveNamed($name, $inner, $required, $description);

        // Re-mark as nullable (the union includes null).
        return new SchemaField(
            name: $field->name,
            type: $field->type,
            nullable: true,
            required: $field->required,
            description: $field->description,
            properties: $field->properties,
            items: $field->items,
            class: $field->class,
        );
    }

    /**
     * @return array<string, SchemaField>
     */
    private function resolveNested(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }
            $properties[$property->getName()] = $this->resolveProperty($property);
        }

        return $properties;
    }

    private function readDescription(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(JsonFicatorDescription::class);
        if (count($attributes) === 0) {
            return null;
        }

        /** @var JsonFicatorDescription $instance */
        $instance = $attributes[0]->newInstance();

        return $instance->description;
    }

    private function resolveRequired(ReflectionProperty $property): bool
    {
        $hasRequired = $property->getAttributes(JsonFicatorRequired::class) !== [];
        $hasOptional = $property->getAttributes(JsonFicatorOptional::class) !== [];

        if ($hasRequired && $hasOptional) {
            throw new SchemaException(
                "Property '{$property->getName()}' cannot be both required and optional."
            );
        }

        if ($hasRequired) {
            return true;
        }

        if ($hasOptional) {
            return false;
        }

        // Default: non-nullable => required, nullable => optional.
        $type = $property->getType();
        if ($type === null) {
            return true;
        }

        return !$type->allowsNull();
    }
}