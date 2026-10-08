<?php

declare(strict_types=1);

namespace JsonFicator\Exception;

/**
 * Thrown when the raw LLM response cannot be parsed into a valid JSON object
 * or fails local validation against the requested schema.
 */
class InvalidResponseException extends JsonFicatorException
{
    /**
     * @param string[] $validationErrors Human-readable list of validation problems.
     */
    public function __construct(
        string $message,
        private readonly array $validationErrors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string[]
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}