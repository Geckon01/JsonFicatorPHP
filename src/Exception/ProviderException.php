<?php

declare(strict_types=1);

namespace JsonFicator\Exception;

/**
 * Thrown when an LLM provider fails to produce a response.
 *
 * Wraps underlying provider/SDK/network errors so callers can rely on a
 * single, stable exception type. The original error is preserved via
 * {@see \Throwable::getPrevious()}.
 */
class ProviderException extends JsonFicatorException
{
}