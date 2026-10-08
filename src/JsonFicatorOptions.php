<?php

declare(strict_types=1);

namespace JsonFicator;

/**
 * Per-call options that can be passed to {@see JsonFicator::toJson()} and
 * {@see JsonFicator::toModel()}.
 *
 * All fields are optional. Provider-specific options are kept in a separate
 * bag so the core does not need to know about them.
 */
final class JsonFicatorOptions
{
    /**
     * @param float|null $temperature Sampling temperature (0.0 - 2.0).
     * @param int|null $maxTokens Maximum number of tokens in the response.
     * @param string|null $systemPrompt Override the system prompt for this call.
     * @param int|null $timeout Request timeout in seconds.
     * @param string|null $model Override the model for this call.
     * @param array<string, mixed> $providerOptions Provider-specific options (e.g. OpenAI-specific flags).
     */
    public function __construct(
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly ?string $systemPrompt = null,
        public readonly ?int $timeout = null,
        public readonly ?string $model = null,
        public readonly array $providerOptions = [],
    ) {
    }

    /**
     * Create a new options object with one field changed (immutable).
     */
    public function withSystemPrompt(?string $systemPrompt): self
    {
        return new self(
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            systemPrompt: $systemPrompt,
            timeout: $this->timeout,
            model: $this->model,
            providerOptions: $this->providerOptions,
        );
    }

    public function withModel(?string $model): self
    {
        return new self(
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            systemPrompt: $this->systemPrompt,
            timeout: $this->timeout,
            model: $model,
            providerOptions: $this->providerOptions,
        );
    }
}