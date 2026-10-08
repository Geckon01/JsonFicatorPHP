<?php

declare(strict_types=1);

namespace JsonFicator;

use JsonFicator\Exception\SchemaException;
use JsonFicator\Provider\LLMProviderInterface;
use JsonFicator\Response\ResponseParser;
use JsonFicator\Response\Validator;
use JsonFicator\Schema\ArraySchemaResolver;
use JsonFicator\Schema\ClassSchemaResolver;
use JsonFicator\Schema\JsonFicatorSchema;
use JsonFicator\Schema\SchemaResolver;

/**
 * Main entry point for the JsonFicator library.
 *
 * Usage:
 * ```php
 * $jsonFictator = new JsonFicator(new OpenAIProvider([
 *     'host' => 'https://api.openai.com/v1',
 *     'key'  => 'sk-...',
 *     'model' => 'gpt-4o',
 * ]));
 *
 * // Array schema
 * $result = $jsonFictator->toJson('Колодки на ниссан лиф +799999999', [
 *     'carBrand' => 'string?',
 *     'carModel' => 'string?',
 *     'phoneNumber' => 'string',
 * ]);
 *
 * // Class schema
 * $result = $jsonFictator->toModel('Колодки на ниссан лиф +799999999', VinRequest::class);
 * ```
 */
final class JsonFicator
{
    private readonly LLMProviderInterface $provider;
    private readonly ResponseParser $parser;
    private readonly Validator $validator;
    private readonly ArraySchemaResolver $arrayResolver;
    private readonly ClassSchemaResolver $classResolver;

    /**
     * @param LLMProviderInterface $provider The LLM provider to use.
     */
    public function __construct(
        LLMProviderInterface $provider,
    ) {
        $this->provider = $provider;
        $this->parser = new ResponseParser();
        $this->validator = new Validator();
        $this->arrayResolver = new ArraySchemaResolver();
        $this->classResolver = new ClassSchemaResolver();
    }

    /**
     * Extract structured data from text using an array schema.
     *
     * @param string $input The raw user text.
     * @param array<string, mixed> $schema The array-syntax schema.
     * @param JsonFicatorOptions|null $options Per-call options.
     *
     * @return JsonFicatorResult
     *
     * @throws SchemaException
     * @throws \JsonFicator\Exception\ProviderException
     * @throws \JsonFicator\Exception\InvalidResponseException
     */
    public function toJson(
        string $input,
        array $schema,
        ?JsonFicatorOptions $options = null,
    ): JsonFicatorResult {
        $internalSchema = $this->arrayResolver->resolve($schema);

        return $this->extract($input, $internalSchema, $options);
    }

    /**
     * Extract structured data from text using a class schema.
     *
     * @template T of IJsonFicatorModel
     * @param string $input The raw user text.
     * @param class-string<T> $class The model class.
     * @param JsonFicatorOptions|null $options Per-call options.
     *
     * @return T
     *
     * @throws SchemaException
     * @throws \JsonFicator\Exception\ProviderException
     * @throws \JsonFicator\Exception\InvalidResponseException
     * @throws \JsonFicator\Exception\ModelHydrationException
     */
    public function toModel(
        string $input,
        string $class,
        ?JsonFicatorOptions $options = null,
    ): object {
        $internalSchema = $this->classResolver->resolve($class);
        $result = $this->extract($input, $internalSchema, $options);

        return $result->toModel($class);
    }

    /**
     * Core extraction pipeline.
     *
     * @param string $input
     * @param JsonFicatorSchema $schema
     * @param JsonFicatorOptions|null $options
     *
     * @return JsonFicatorResult
     */
    private function extract(
        string $input,
        JsonFicatorSchema $schema,
        ?JsonFicatorOptions $options = null,
    ): JsonFicatorResult {
        // 1. Generate via provider.
        $rawResponse = $this->provider->generate($input, $schema, $options);

        // 2. Parse.
        $data = $this->parser->parse($rawResponse);

        // 3. Validate.
        $this->validator->validate($data, $schema);

        // 4. Return result.
        return new JsonFicatorResult($data, $schema);
    }
}