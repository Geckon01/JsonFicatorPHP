<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Fixtures;

use JsonFicator\JsonFicatorOptions;
use JsonFicator\Provider\LLMProviderInterface;
use JsonFicator\Schema\JsonFicatorSchema;

/**
 * A fake LLM provider for unit tests that returns a pre-configured array.
 */
final class FakeProvider implements LLMProviderInterface
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        private readonly array $response,
    ) {
    }

    public function generate(
        string $input,
        JsonFicatorSchema $schema,
        ?JsonFicatorOptions $options = null,
    ): array {
        return $this->response;
    }
}
