<?php

declare(strict_types=1);

namespace JsonFicator\Response;

use JsonFicator\Exception\InvalidResponseException;
use JsonFicator\Schema\JsonFicatorSchema;
use JsonFicator\Schema\SchemaField;

/**
 * Validates a parsed array against the internal schema.
 *
 * Checks:
 * - required fields are present
 * - nullable fields may be null
 * - primitive types match
 * - arrays contain items of the correct type
 * - nested objects match their sub-schema
 * - no unexpected fields
 *
 * This is a lightweight, purpose-built validator. It does not implement the
 * full JSON Schema spec, but covers the subset that JsonFicator supports.
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidResponseException If validation fails.
     */
    public function validate(array $data, JsonFicatorSchema $schema): void
    {
        $errors = [];

        // Check for unexpected fields.
        foreach (array_keys($data) as $key) {
            if (!$schema->hasField((string) $key)) {
                $errors[] = "Unexpected field '{$key}'.";
            }
        }

        // Check each schema field.
        foreach ($schema->fields as $name => $field) {
            $this->validateField($data, $name, $field, $errors);
        }

        if ($errors !== []) {
            throw new InvalidResponseException(
                'Validation failed: ' . implode(' ', $errors),
                $errors,
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $errors
     */
    private function validateField(
        array $data,
        string $name,
        SchemaField $field,
        array &$errors,
    ): void {
        $present = array_key_exists($name, $data);
        $value = $data[$name] ?? null;

        if (!$present) {
            if ($field->required) {
                $errors[] = "Required field '{$name}' is missing.";
            }
            return;
        }

        if ($value === null) {
            if (!$field->nullable) {
                $errors[] = "Field '{$name}' is null but not nullable.";
            }
            return;
        }

        $this->validateValue($name, $value, $field, $errors);
    }

    /**
     * @param string[] $errors
     */
    private function validateValue(
        string $name,
        mixed $value,
        SchemaField $field,
        array &$errors,
    ): void {
        switch ($field->type) {
            case 'string':
                if (!is_string($value)) {
                    $errors[] = "Field '{$name}' must be a string, got " . get_debug_type($value) . '.';
                }
                break;

            case 'int':
                if (!is_int($value)) {
                    $errors[] = "Field '{$name}' must be an integer, got " . get_debug_type($value) . '.';
                }
                break;

            case 'float':
                if (!is_int($value) && !is_float($value)) {
                    $errors[] = "Field '{$name}' must be a number, got " . get_debug_type($value) . '.';
                }
                break;

            case 'bool':
                if (!is_bool($value)) {
                    $errors[] = "Field '{$name}' must be a boolean, got " . get_debug_type($value) . '.';
                }
                break;

            case 'array':
                if (!is_array($value)) {
                    $errors[] = "Field '{$name}' must be an array, got " . get_debug_type($value) . '.';
                    break;
                }
                if ($field->items !== null) {
                    foreach ($value as $index => $item) {
                        $this->validateValue("{$name}[{$index}]", $item, $field->items, $errors);
                    }
                }
                break;

            case 'object':
                if (!is_array($value)) {
                    $errors[] = "Field '{$name}' must be an object, got " . get_debug_type($value) . '.';
                    break;
                }
                if ($field->properties !== []) {
                    $this->validateNestedObject($name, $value, $field, $errors);
                }
                break;

            default:
                $errors[] = "Field '{$name}' has unsupported type '{$field->type}'.";
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $errors
     */
    private function validateNestedObject(
        string $name,
        array $data,
        SchemaField $field,
        array &$errors,
    ): void {
        // Check for unexpected fields in the nested object.
        foreach (array_keys($data) as $key) {
            if (!isset($field->properties[(string) $key])) {
                $errors[] = "Unexpected field '{$name}.{$key}'.";
            }
        }

        foreach ($field->properties as $childName => $childField) {
            $this->validateField($data, $childName, $childField, $errors);
            // Prefix errors with the parent path.
        }
    }
}