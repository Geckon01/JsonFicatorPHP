<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

/**
 * A single field of the internal, provider-agnostic schema.
 *
 * This is the canonical representation of a field after it has been resolved
 * from either the array syntax or a PHP class. It is independent of any LLM
 * provider and can be converted to JSON Schema, validated against, and used
 * to hydrate models.
 */
final class SchemaField
{
    /**
     * @param string|null $description Human-readable description (used in prompts / JSON Schema).
     * @param SchemaField[] $properties Child fields when $type is "object".
     * @param SchemaField|null $items The item schema when $type is "array".
     * @param string|null $class FQCN of the model this field maps to (for hydration), if any.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $nullable,
        public readonly bool $required,
        public readonly ?string $description = null,
        public readonly array $properties = [],
        public readonly ?SchemaField $items = null,
        public readonly ?string $class = null,
    ) {
    }

    /**
     * Whether the field holds a primitive value (string/int/float/bool).
     */
    public function isPrimitive(): bool
    {
        return in_array($this->type, ['string', 'int', 'float', 'bool'], true);
    }

    /**
     * Whether the field is a structured value (object or array).
     */
    public function isStructured(): bool
    {
        return $this->type === 'object' || $this->type === 'array';
    }
}