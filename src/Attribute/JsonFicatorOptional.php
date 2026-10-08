<?php

declare(strict_types=1);

namespace JsonFicator\Attribute;

use Attribute;

/**
 * Marks a model property as optional for extraction.
 *
 * This overrides the default "required" semantics of a non-nullable property,
 * telling the LLM that the value may be absent (in which case it will be
 * filled with the property's default value, or null if none is set).
 *
 * @example
 * ```php
 * #[JsonFicatorOptional]
 * public string $year = 'unknown';
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class JsonFicatorOptional
{
}