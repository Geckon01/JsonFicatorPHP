<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

use JsonFicator\Exception\SchemaException;

/**
 * Resolves the array-syntax schema into the internal schema.
 *
 * Supported syntax (values are strings):
 *
 * - `string`, `int`, `float`, `bool` — primitive types
 * - `string?`, `int?`, ... — nullable primitive types
 * - `array` — array of unknown items
 * - `array<string>`, `array<int>`, `array<bool>`, `array<float>` — typed array
 * - `array<object>` — array of objects (item schema must be given via a nested
 *   definition; see {@see self::resolveNested()})
 * - `object` — object with unknown shape
 *
 * Nested objects are expressed by giving the value as an array of child
 * definitions:
 *
 * ```php
 * [
 *     'car' => [
 *         'brand' => 'string',
 *         'model' => 'string?',
 *     ],
 * ]
 * ```
 *
 * The syntax is intentionally small and validated strictly. Unknown types and
 * malformed syntax throw {@see SchemaException}.
 */
final class ArraySchemaResolver implements SchemaResolver
{
    /**
     * Primitive types that are directly supported.
     *
     * @var string[]
     */
    private const PRIMITIVES = ['string', 'int', 'float', 'bool'];

    /**
     * @param string[] $supportedTypes Override the set of supported primitive types.
     */
    public function __construct(
        private readonly array $supportedTypes = self::PRIMITIVES,
    ) {
    }

    public function resolve(mixed $definition): JsonFicatorSchema
    {
        if (!is_array($definition)) {
            throw new SchemaException(
                'Array schema must be an array of "field => type" pairs, got: ' . get_debug_type($definition)
            );
        }

        $fields = [];
        foreach ($definition as $name => $type) {
            $name = (string) $name;
            if ($name === '') {
                throw new SchemaException('Field name cannot be empty.');
            }
            $fields[$name] = $this->resolveField($name, $type);
        }

        return new JsonFicatorSchema($fields);
    }

    /**
     * @param mixed $type
     */
    private function resolveField(string $name, mixed $type): SchemaField
    {
        // Nested object expressed as an array of child definitions.
        if (is_array($type)) {
            $properties = [];
            foreach ($type as $childName => $childType) {
                $childName = (string) $childName;
                if ($childName === '') {
                    throw new SchemaException("Field '{$name}' has an empty child field name.");
                }
                $properties[$childName] = $this->resolveField($childName, $childType);
            }

            return new SchemaField(
                name: $name,
                type: 'object',
                nullable: false,
                required: true,
                properties: $properties,
            );
        }

        if (!is_string($type)) {
            throw new SchemaException(
                "Field '{$name}' must be a type string or a nested array, got: " . get_debug_type($type)
            );
        }

        return $this->parseTypeString($name, $type);
    }

    /**
     * Parse a single type string such as "string?", "array<int>", "object".
     */
    private function parseTypeString(string $name, string $type): SchemaField
    {
        $type = trim($type);
        if ($type === '') {
            throw new SchemaException("Field '{$name}' has an empty type.");
        }

        $nullable = str_ends_with($type, '?');
        if ($nullable) {
            $type = rtrim(substr($type, 0, -1), ' ');
        }

        // array<...>
        if (str_starts_with($type, 'array<') && str_ends_with($type, '>')) {
            $inner = trim(substr($type, 6, -1));
            $items = $this->parseTypeString($name . '[]', $inner);

            return new SchemaField(
                name: $name,
                type: 'array',
                nullable: $nullable,
                required: !$nullable,
                items: $items,
            );
        }

        if ($type === 'array') {
            return new SchemaField(
                name: $name,
                type: 'array',
                nullable: $nullable,
                required: !$nullable,
            );
        }

        if ($type === 'object') {
            return new SchemaField(
                name: $name,
                type: 'object',
                nullable: $nullable,
                required: !$nullable,
            );
        }

        if (in_array($type, $this->supportedTypes, true)) {
            return new SchemaField(
                name: $name,
                type: $type,
                nullable: $nullable,
                required: !$nullable,
            );
        }

        throw new SchemaException(
            "Field '{$name}' has an unsupported type '{$type}'. "
            . 'Supported types: ' . implode(', ', $this->supportedTypes)
            . ', array, array<type>, object, and nested arrays.'
        );
    }
}