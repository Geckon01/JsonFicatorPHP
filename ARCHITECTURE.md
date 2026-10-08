# JsonFicator Architecture

## Overview

JsonFicator is a pipeline-based library that transforms unstructured natural language into structured data. The pipeline is:

```
Input text
    → Schema resolution (Array or Class)
    → JSON Schema conversion (for OpenAI strict mode)
    → Prompt building (system + user messages)
    → LLM generation (OpenAIProvider)
    → Response parsing (strip markdown, decode JSON)
    → Validation (check types, required fields, unexpected keys)
    → Hydration (array → PHP model)
    → JsonFicatorResult
```

## Core Components

### JsonFicator (Facade)

Entry point. Delegates to the pipeline:

- `toJson(string $input, array|class-string $schema, ?JsonFicatorOptions $options): JsonFicatorResult` — returns array result
- `toModel(string $input, class-string $schema, ?JsonFicatorOptions $options): JsonFicatorResult` — returns hydrated model

### Schema Layer

Internal representation is decoupled from any LLM provider.

#### JsonFicatorSchema

- `fields: SchemaField[]`
- Optional `modelClass: class-string` for class-based schemas

#### SchemaField

- `name: string`
- `type: string` — primitive (`string`, `int`, `float`, `bool`) or `array` or `object`
- `nullable: bool`
- `required: bool`
- `description: ?string`
- `properties: ?SchemaField[]` — for nested objects
- `items: ?SchemaField` — for typed arrays
- `class: ?class-string` — for nested model classes

#### Resolvers

- `ArraySchemaResolver` — parses inline array syntax:
  - `string`, `int`, `float`, `bool`
  - `?string` for nullable
  - `array`, `array<string>`, `array<int>`, `array<array>`
- `ClassSchemaResolver` — uses PHP reflection:
  - Supports primitives, nullable, arrays, nested `IJsonFicatorModel` classes
  - Reads `JsonFicatorDescription`, `JsonFicatorRequired`, `JsonFicatorOptional` attributes

#### JsonSchemaConverter

Converts internal `JsonFicatorSchema` to OpenAI-compatible JSON Schema:

- `type: object` with `properties`
- `required` array based on `SchemaField.required`
- Nullable fields use `anyOf: [{type}, {type: "null"}]`
- Arrays use `items` schema
- Nested objects recurse into `properties`
- `additionalProperties: false` when `strict: true`

### Provider Layer

#### LLMProviderInterface

```php
public function generate(
    string $input,
    JsonFicatorSchema $schema,
    ?JsonFicatorOptions $options = null,
): array;
```

Returns raw decoded associative array from LLM.

#### OpenAIProvider

- Uses `openai-php/client` via `new \OpenAI\Factory()` (avoids global `\OpenAI` class collision)
- Builds `ChatCompletion` with `ResponseFormat::createJsonSchema(..., strict: true)`
- Normalizes all exceptions to `ProviderException`
- Extracts `choices[0].message.content` and decodes JSON

### Response Pipeline

#### ResponseParser

- Accepts `array` (already decoded) or `string` (raw JSON)
- Strips markdown code fences (```json ... ```)
- Decodes JSON string to associative array
- Throws `InvalidResponseException` if result is not an object (associative array)

#### Validator

Validates parsed data against internal `JsonFicatorSchema`:

- Required fields present
- Nullable rules respected
- Primitive type checks (`string`, `int`, `float`, `bool`)
- Array items validated recursively
- Nested objects validated against sub-schema
- Unexpected fields rejected when schema has `modelClass` (class-based) or strict mode

#### ModelHydrator

Hydrates validated array into PHP model via reflection:

- Creates instance without constructor
- Sets public properties directly
- Handles nullable, nested models, arrays
- Supports union types (picks first matching type)
- Casts `int` → `string` when needed
- Throws `ModelHydrationException` on type mismatch

### Options & Result

#### JsonFicatorOptions

Immutable per-call configuration:

- `model: ?string`
- `temperature: ?float`
- `maxTokens: ?int`

Methods: `withModel()`, `withTemperature()`, `withMaxTokens()`

#### JsonFicatorResult

Immutable wrapper:

- `toArray(): array`
- `toJson(): string`
- `toModel(): object` (requires schema was a class)
- `get(string $key, mixed $default = null): mixed`
- `getSchema(): JsonFicatorSchema`

## Exception Hierarchy

```
JsonFicatorException (base)
├── SchemaException
├── ProviderException
├── InvalidResponseException
└── ModelHydrationException
```

All extend `\Exception` and are located in `src/Exception/`.

## Design Decisions

1. **Internal schema representation** — decoupled from OpenAI JSON Schema, allowing future providers (Claude, Gemini) to reuse the same validators and hydrators.
2. **Strict mode by default** — `additionalProperties: false` prevents LLM hallucination of extra fields.
3. **Readonly properties** — all service classes use `private readonly` for immutability.
4. **No constructor in models** — hydrator uses `newInstanceWithoutConstructor()` to support plain DTOs.
5. **Attributes over docblocks** — PHP 8 attributes are native, cacheable, and type-safe.
6. **Factory over global class** — `new \OpenAI\Factory()` avoids fatal error when `vendor/openai-php/client/src/OpenAI.php` (global class) is loaded via Composer `files` autoload.

## Testing Strategy

- **Unit tests** — 76 tests covering every component in isolation with fake provider
- **Integration tests** — gated by `JSONFICATOR_OPENAI_API_KEY` env var; tests real API with small model (`gpt-4o-mini`)
- **Fixtures** — `tests/Fixtures/` contains sample models (`VinRequest`, `Car`, `NestedRequest`, `DescribedModel`, `FakeProvider`)
