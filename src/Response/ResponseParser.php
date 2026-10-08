<?php

declare(strict_types=1);

namespace JsonFicator\Response;

use JsonFicator\Exception\InvalidResponseException;

/**
 * Parses the raw LLM response into a PHP array.
 *
 * Handles:
 * - JSON strings (with optional markdown code fences)
 * - Already-decoded arrays (from providers that return arrays directly)
 * - Malformed JSON
 */
final class ResponseParser
{
    /**
     * @param mixed $raw The raw response from the provider.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidResponseException If the response cannot be parsed.
     */
    public function parse(mixed $raw): array
    {
        if (is_array($raw)) {
            return $this->ensureObject($raw);
        }

        if (is_string($raw)) {
            return $this->parseString($raw);
        }

        throw new InvalidResponseException(
            'LLM response must be a string or array, got: ' . get_debug_type($raw)
        );
    }

    private function parseString(string $raw): array
    {
        $cleaned = $this->stripCodeFences(trim($raw));

        $decoded = json_decode($cleaned, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException(
                'Failed to parse LLM response as JSON: ' . json_last_error_msg()
                . "\nRaw response: " . substr($cleaned, 0, 500)
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidResponseException(
                'LLM response is not a JSON object. Got: ' . get_debug_type($decoded)
            );
        }

        return $this->ensureObject($decoded);
    }

    private function stripCodeFences(string $text): string
    {
        // Remove ```json ... ``` or ``` ... ```
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        return trim($text);
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function ensureObject(array $array): array
    {
        // Ensure it's an associative array (object), not a list.
        if ($array === [] || array_keys($array) !== range(0, count($array) - 1)) {
            return $array;
        }

        throw new InvalidResponseException(
            'LLM response must be a JSON object, not a JSON array.'
        );
    }
}