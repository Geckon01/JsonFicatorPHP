<?php

declare(strict_types=1);

namespace JsonFicator;

use JsonFicator\Exception\ModelHydrationException;
use JsonFicator\Response\ModelHydrator;
use JsonFicator\Schema\JsonFicatorSchema;

/**
 * The result of a JsonFicator extraction.
 *
 * Wraps the validated array and provides access to it in different forms:
 * - {@see toArray()} — the raw validated array
 * - {@see toJson()} — a JSON string
 * - {@see toModel()} — a hydrated PHP model instance
 *
 * The result is immutable.
 */
final class JsonFicatorResult
{
    private readonly ModelHydrator $hydrator;

    /**
     * @param array<string, mixed> $data The validated array.
     * @param JsonFicatorSchema $schema The schema the data was validated against.
     */
    public function __construct(
        private readonly array $data,
        private readonly JsonFicatorSchema $schema,
    ) {
        $this->hydrator = new ModelHydrator();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param int $options json_encode options.
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->data, $options | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode result as JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Hydrate the result into a PHP model.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     *
     * @throws ModelHydrationException
     */
    public function toModel(string $class): object
    {
        return $this->hydrator->hydrate($this->data, $class);
    }

    /**
     * @return JsonFicatorSchema
     */
    public function getSchema(): JsonFicatorSchema
    {
        return $this->schema;
    }

    /**
     * @param string $key
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}