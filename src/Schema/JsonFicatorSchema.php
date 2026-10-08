<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

/**
 * The internal, provider-agnostic representation of a schema.
 *
 * A schema is a set of named fields describing the structure the LLM must
 * produce. It is produced by a {@see SchemaResolver} and consumed by the
 * JSON Schema converter, the validator and the model hydrator.
 *
 * The schema is "flat" in the sense that it always describes a single object
 * (the top-level result). Nested objects are expressed via {@see SchemaField::$properties}.
 */
final class JsonFicatorSchema
{
    /**
     * @param SchemaField[] $fields Map of field name => SchemaField.
     * @param string|null $modelClass FQCN of the model this schema was resolved from, if any.
     */
    public function __construct(
        public readonly array $fields,
        public readonly ?string $modelClass = null,
    ) {
    }

    /**
     * @return string[]
     */
    public function fieldNames(): array
    {
        return array_keys($this->fields);
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function getField(string $name): ?SchemaField
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return SchemaField[]
     */
    public function requiredFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (SchemaField $field): bool => $field->required,
        ));
    }
}