<?php

declare(strict_types=1);

namespace JsonFicator\Response;

use JsonFicator\Exception\ModelHydrationException;
use JsonFicator\Schema\JsonFicatorSchema;
use JsonFicator\Schema\SchemaField;

/**
 * Hydrates a validated array into a PHP model instance.
 *
 * Handles:
 * - primitive type casting (int, float, bool, string)
 * - nullable fields
 * - nested models (recursive hydration)
 * - arrays
 *
 * Throws {@see ModelHydrationException} on type mismatch or missing property.
 */
final class ModelHydrator
{
    /**
     * @param array<string, mixed> $data
     * @param class-string $class
     *
     * @return object
     *
     * @throws ModelHydrationException
     */
    public function hydrate(array $data, string $class): object
    {
        if (!class_exists($class)) {
            throw new ModelHydrationException("Class '{$class}' does not exist.");
        }

        $instance = new $class();
        $reflection = new \ReflectionClass($class);

        foreach ($data as $propertyName => $value) {
            if (!$reflection->hasProperty($propertyName)) {
                throw new ModelHydrationException(
                    "Class '{$class}' has no property '{$propertyName}'."
                );
            }

            $property = $reflection->getProperty($propertyName);

            if (!$property->isPublic()) {
                throw new ModelHydrationException(
                    "Property '{$propertyName}' in class '{$class}' is not public."
                );
            }

            $this->setPropertyValue($instance, $property, $value, $class);
        }

        return $instance;
    }

    /**
     * @param object $instance
     * @param \ReflectionProperty $property
     * @param mixed $value
     * @param class-string $class
     *
     * @throws ModelHydrationException
     */
    private function setPropertyValue(
        object $instance,
        \ReflectionProperty $property,
        mixed $value,
        string $class,
    ): void {
        $propertyName = $property->getName();

        if ($value === null) {
            $property->setValue($instance, null);
            return;
        }

        $type = $property->getType();

        if ($type === null) {
            // Untyped property — set as-is.
            $property->setValue($instance, $value);
            return;
        }

        if ($type instanceof \ReflectionUnionType) {
            $this->setUnionValue($instance, $property, $value, $class);
            return;
        }

        if (!($type instanceof \ReflectionNamedType)) {
            throw new ModelHydrationException(
                "Property '{$propertyName}' in class '{$class}' has an unsupported type."
            );
        }

        $typeName = $type->getName();

        // Nested model
        if (class_exists($typeName) && is_array($value)) {
            $nested = $this->hydrate($value, $typeName);
            $property->setValue($instance, $nested);
            return;
        }

        // Array
        if ($typeName === 'array' || $typeName === 'list') {
            if (!is_array($value)) {
                throw new ModelHydrationException(
                    "Property '{$propertyName}' in class '{$class}' expects an array, got " . get_debug_type($value) . '.'
                );
            }
            $property->setValue($instance, $value);
            return;
        }

        // Primitives
        $this->castAndSet($instance, $property, $value, $typeName, $class);
    }

    /**
     * @param object $instance
     * @param \ReflectionProperty $property
     * @param mixed $value
     * @param class-string $class
     *
     * @throws ModelHydrationException
     */
    private function setUnionValue(
        object $instance,
        \ReflectionProperty $property,
        mixed $value,
        string $class,
    ): void {
        $propertyName = $property->getName();
        $types = $property->getType();

        if (!$types instanceof \ReflectionUnionType) {
            return;
        }

        $nonNullTypes = array_values(array_filter(
            $types->getTypes(),
            static fn ($t): bool => !($t instanceof \ReflectionNamedType && $t->getName() === 'null'),
        ));

        if (count($nonNullTypes) !== 1 || !($nonNullTypes[0] instanceof \ReflectionNamedType)) {
            throw new ModelHydrationException(
                "Property '{$propertyName}' in class '{$class}' has an unsupported union type."
            );
        }

        $innerType = $nonNullTypes[0]->getName();

        if (class_exists($innerType) && is_array($value)) {
            $nested = $this->hydrate($value, $innerType);
            $property->setValue($instance, $nested);
            return;
        }

        if ($innerType === 'array' || $innerType === 'list') {
            $property->setValue($instance, $value);
            return;
        }

        $this->castAndSet($instance, $property, $value, $innerType, $class);
    }

    /**
     * @param object $instance
     * @param \ReflectionProperty $property
     * @param mixed $value
     * @param class-string $class
     *
     * @throws ModelHydrationException
     */
    private function castAndSet(
        object $instance,
        \ReflectionProperty $property,
        mixed $value,
        string $typeName,
        string $class,
    ): void {
        $propertyName = $property->getName();

        switch ($typeName) {
            case 'string':
                if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
                    throw new ModelHydrationException(
                        "Property '{$propertyName}' in class '{$class}' expects a string, got " . get_debug_type($value) . '.'
                    );
                }
                $property->setValue($instance, (string) $value);
                break;

            case 'int':
                if (!is_int($value) && !is_string($value)) {
                    throw new ModelHydrationException(
                        "Property '{$propertyName}' in class '{$class}' expects an int, got " . get_debug_type($value) . '.'
                    );
                }
                $property->setValue($instance, (int) $value);
                break;

            case 'float':
                if (!is_int($value) && !is_float($value) && !is_string($value)) {
                    throw new ModelHydrationException(
                        "Property '{$propertyName}' in class '{$class}' expects a float, got " . get_debug_type($value) . '.'
                    );
                }
                $property->setValue($instance, (float) $value);
                break;

            case 'bool':
                if (!is_bool($value) && !is_int($value)) {
                    throw new ModelHydrationException(
                        "Property '{$propertyName}' in class '{$class}' expects a bool, got " . get_debug_type($value) . '.'
                    );
                }
                $property->setValue($instance, (bool) $value);
                break;

            default:
                throw new ModelHydrationException(
                    "Property '{$propertyName}' in class '{$class}' has unsupported type '{$typeName}'."
                );
        }
    }
}