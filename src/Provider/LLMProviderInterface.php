<?php

declare(strict_types=1);

namespace JsonFicator\Provider;

use JsonFicator\JsonFicatorOptions;
use JsonFicator\Schema\JsonFicatorSchema;

/**
 * Contract for LLM providers.
 *
 * A provider takes a raw user input and an internal schema, sends them to an
 * LLM, and returns the raw response as a PHP array (the decoded JSON object).
 *
 * Providers are responsible for:
 * - building the request (prompt, schema, model, options)
 * - calling the underlying API
 * - normalising errors into {@see \JsonFicator\Exception\ProviderException}
 *
 * Providers are NOT responsible for:
 * - parsing the response (done by {@see \JsonFicator\Response\ResponseParser})
 * - validating the response (done by {@see \JsonFicator\Response\Validator})
 * - hydrating models (done by {@see \JsonFicator\Response\ModelHydrator})
 */
interface LLMProviderInterface
{
    /**
     * Generate a structured response from the LLM.
     *
     * @param string $input The raw user text to extract data from.
     * @param JsonFicatorSchema $schema The internal schema describing the expected structure.
     * @param JsonFicatorOptions|null $options Per-call options.
     *
     * @return array<string, mixed> The decoded JSON object returned by the LLM.
     *
     * @throws \JsonFicator\Exception\ProviderException If the provider fails.
     */
    public function generate(
        string $input,
        JsonFicatorSchema $schema,
        ?JsonFicatorOptions $options = null,
    ): array;
}