<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

use JsonFicator\Exception\SchemaException;

/**
 * Converts the internal {@see JsonFicatorSchema} into a JSON Schema document
 * suitable for LLM providers.
 *
 * The generated schema is compatible with OpenAI "strict" structured outputs:
 * - `type: object`
 * - `additionalProperties: false`
 * - `required` lists every non-nullable field
 * - nullable fields use `anyOf: [{type: ...}, {type: "null"}]`
 *
 * This keeps the core independent of any specific provider while producing a
 * schema that OpenAI can enforce natively.
 */
final class JsonSchemaConverter
{
    /**
     * @param bool $strict Whether to emit strict-mode compatible output
     *                     (additionalProperties: false, required for all non-null).
     */
    public function __construct(
        private readonly bool $strict = true,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function convert(JsonFicatorSchema $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($schema->fields as $name => $field) {
            $properties[$name] = $this->convertField($field);

            if ($this->strict && $field->required) {
                $required[] = $name;
            }
        }

        $result = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($this->strict) {
            $result['additionalProperties'] = false;
            $result['required'] = $required;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function convertField(SchemaField $field): array
    {
        $base = $this->convertBase($field);

        if ($field->nullable) {
            // OpenAI strict mode requires anyOf with null for nullable fields.
            return [
                'anyOf' => [$base, ['type' => 'null']],
            ];
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function convertBase(SchemaField $field): array
    {
        $result = [];

        switch ($field->type) {
            case 'string':
                $result['type'] = 'string';
                break;
            case 'int':
                $result['type'] = 'integer';
                break;
            case 'float':
                $result['type'] = 'number';
                break;
            case 'bool':
                $result['type'] = 'boolean';
                break;
            case 'array':
                $result['type'] = 'array';
                if ($field->items !== null) {
                    $result['items'] = $this->convertBase($field->items);
                }
                break;
            case 'object':
                $result['type'] = 'object';
                if ($field->properties !== []) {
                    $props = [];
                    $req = [];
                    foreach ($field->properties as $childName => $child) {
                        $props[$childName] = $this->convertField($child);
                        if ($this->strict && $child->required) {
                            $req[] = $childName;
                        }
                    }
                    $result['properties'] = $props;
                    if ($this->strict) {
                        $result['additionalProperties'] = false;
                        $result['required'] = $req;
                    }
                }
                break;
            default:
                throw new SchemaException("Unsupported field type '{$field->type}'.");
        }

        if ($field->description !== null) {
            $result['description'] = $field->description;
        }

        return $result;
    }
}