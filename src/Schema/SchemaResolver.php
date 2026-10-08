<?php

declare(strict_types=1);

namespace JsonFicator\Schema;

/**
 * Converts a user-provided schema definition into the internal
 * {@see JsonFicatorSchema}.
 *
 * There is one resolver per schema source (array syntax, PHP class). Keeping
 * resolution separate from the LLM provider keeps the core provider-agnostic.
 */
interface SchemaResolver
{
    /**
     * @param mixed $definition The raw schema definition (array or class name).
     */
    public function resolve(mixed $definition): JsonFicatorSchema;
}