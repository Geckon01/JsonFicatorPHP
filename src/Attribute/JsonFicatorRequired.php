<?php

declare(strict_types=1);

namespace JsonFicator\Attribute;

use Attribute;

/**
 * Marks a model property as required for extraction.
 *
 * By default, a non-nullable property is already treated as required. This
 * attribute is useful to force a nullable property to be required, or to make
 * the intent explicit.
 *
 * @example
 * ```php
 * #[JsonFicatorRequired]
 * public ?string $phone;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class JsonFicatorRequired
{
}