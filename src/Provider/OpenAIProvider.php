<?php

declare(strict_types=1);

namespace JsonFicator\Provider;

use JsonFicator\Exception\ProviderException;
use JsonFicator\JsonFicatorOptions;
use JsonFicator\Prompt\PromptBuilder;
use JsonFicator\Schema\JsonFicatorSchema;
use JsonFicator\Schema\JsonSchemaConverter;
use OpenAI\Client;

/**
 * LLM provider backed by the OpenAI API (or any OpenAI-compatible endpoint).
 *
 * Uses the official {@see \OpenAI\Client} from the `openai-php/client` package.
 * Supports:
 * - custom base URL / host (for OpenAI-compatible endpoints)
 * - custom API key
 * - custom model
 * - strict structured outputs (JSON Schema)
 *
 * Configuration is passed as an array:
 * ```php
 * new OpenAIProvider([
 *     'host' => 'https://api.openai.com/v1',
 *     'key'  => 'sk-...',
 *     'model' => 'gpt-4o',
 * ]);
 * ```
 */
final class OpenAIProvider implements LLMProviderInterface
{
    private readonly Client $client;
    private readonly string $defaultModel;
    private readonly PromptBuilder $promptBuilder;
    private readonly JsonSchemaConverter $schemaConverter;

    /**
     * @param array{
     *     host?: string,
     *     key?: string,
     *     model?: string,
     *     promptBuilder?: PromptBuilder,
     *     strict?: bool,
     * } $config
     */
    public function __construct(
        array $config = [],
    ) {
        $host = $config['host'] ?? 'https://api.openai.com/v1';
        $key = $config['key'] ?? ($_SERVER['JSONFICATOR_OPENAI_API_KEY'] ?? $_ENV['JSONFICATOR_OPENAI_API_KEY'] ?? null);

        if ($key === null || $key === '') {
            throw new ProviderException(
                'OpenAI API key is required. Provide it via the "key" config option or the JSONFICATOR_OPENAI_API_KEY environment variable.'
            );
        }

        $this->defaultModel = $config['model'] ?? 'gpt-4o';
        $this->promptBuilder = $config['promptBuilder'] ?? new PromptBuilder();
        $this->schemaConverter = new JsonSchemaConverter(strict: $config['strict'] ?? true);

        $this->client = (new \OpenAI\Factory())
            ->withApiKey($key)
            ->withBaseUri($host)
            ->make();
    }

    public function generate(
        string $input,
        JsonFicatorSchema $schema,
        ?JsonFicatorOptions $options = null,
    ): array {
        $jsonSchema = $this->schemaConverter->convert($schema);
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($schema, $jsonSchema);
        $userMessage = $this->promptBuilder->buildUserMessage($input);

        $model = $options?->model ?? $this->defaultModel;

        $parameters = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'extraction',
                    'schema' => $jsonSchema,
                    'strict' => true,
                ],
            ],
        ];

        if ($options?->temperature !== null) {
            $parameters['temperature'] = $options->temperature;
        }
        if ($options?->maxTokens !== null) {
            $parameters['max_tokens'] = $options->maxTokens;
        }

        try {
            $response = $this->client->chat()->create($parameters);
        } catch (\Throwable $e) {
            throw new ProviderException(
                'OpenAI API request failed: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $this->extractContent($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractContent(\OpenAI\Responses\Chat\CreateResponse $response): array
    {
        $message = $response->choices[0]->message;

        if ($message->content === null || $message->content === '') {
            throw new ProviderException(
                'OpenAI returned an empty response. The model may not support structured outputs.'
            );
        }

        $decoded = json_decode($message->content, true);

        if (!is_array($decoded)) {
            throw new ProviderException(
                'Failed to decode OpenAI response as JSON: ' . substr($message->content, 0, 200)
            );
        }

        return $decoded;
    }
}