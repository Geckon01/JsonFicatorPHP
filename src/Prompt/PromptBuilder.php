<?php

declare(strict_types=1);

namespace JsonFicator\Prompt;

use JsonFicator\Schema\JsonFicatorSchema;

/**
 * Builds the system prompt that instructs the LLM how to extract data.
 *
 * The prompt is intentionally separated from the provider so it can be
 * replaced or customised without touching provider code. The default
 * implementation emphasises the "do not invent values" rule.
 */
final class PromptBuilder
{
    /**
     * @param string $systemPrompt Override the default system prompt template.
     *                             The template may contain {schema} which will be
     *                             replaced with the JSON representation of the schema.
     */
    public function __construct(
        private readonly ?string $systemPrompt = null,
    ) {
    }

    /**
     * Build the system prompt for a given schema.
     */
    public function buildSystemPrompt(JsonFicatorSchema $schema, array $jsonSchema): string
    {
        $template = $this->systemPrompt ?? self::DEFAULT_TEMPLATE;

        return str_replace(
            '{schema}',
            $this->renderSchema($jsonSchema),
            $template,
        );
    }

    /**
     * Build the user message wrapping the raw input.
     */
    public function buildUserMessage(string $input): string
    {
        return "Extract structured data from the following text:\n\n" . $input;
    }

    private function renderSchema(array $jsonSchema): string
    {
        $json = json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }

    private const DEFAULT_TEMPLATE = <<<'PROMPT'
You are a data extraction assistant. Your task is to extract structured data from the user's text according to the provided JSON Schema.

Rules:
1. Extract only values that are explicitly present or unambiguously implied in the text.
2. Do NOT invent, guess, or hallucinate values. If a value is not present in the text and the field is nullable, set it to null.
3. If a value cannot be determined with confidence, set it to null (if nullable) or omit it.
4. Follow the JSON Schema exactly. Do not add fields that are not in the schema.
5. Return ONLY a valid JSON object. No markdown, no explanations, no code fences.

JSON Schema:
{schema}
PROMPT;
}