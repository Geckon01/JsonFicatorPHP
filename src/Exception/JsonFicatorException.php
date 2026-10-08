<?php

declare(strict_types=1);

namespace JsonFicator\Exception;

use Exception;

/**
 * Base exception for all JsonFicator errors.
 *
 * Catch this to handle any error thrown by the library in a single place.
 */
class JsonFicatorException extends Exception
{
}