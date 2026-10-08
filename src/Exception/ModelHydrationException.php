<?php

declare(strict_types=1);

namespace JsonFicator\Exception;

/**
 * Thrown when a validated array cannot be hydrated into a PHP model
 * (e.g. type mismatch, unknown property, missing required property).
 */
class ModelHydrationException extends JsonFicatorException
{
}