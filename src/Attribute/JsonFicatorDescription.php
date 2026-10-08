<?php

declare(strict_types=1);

namespace JsonFicator\Attribute;

use Attribute;

/**
 * Provides a human-readable description for a model property.
 *
 * The description is surfaced in the generated JSON Schema and in the prompt,
 * which helps the LLM understand what value to extract.
 *
 * @example
 * ```php
 * #[JsonFicatorDescription('Марка автомобиля, например Nissan')]
 * public ?string $brand;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class JsonFicatorDescription
{
    public function __construct(
        public readonly string $description,
    ) {
    }
}