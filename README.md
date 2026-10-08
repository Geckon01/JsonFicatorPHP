# JsonFicator

Framework-agnostic PHP library that converts natural language into structured data (arrays or PHP models) according to a schema, using LLM providers (OpenAI-compatible).

## Features

- **Schema-driven extraction** — define the shape of output via PHP arrays or classes
- **Two schema resolvers** — `ArraySchemaResolver` for inline syntax, `ClassSchemaResolver` for reflection-based schemas
- **OpenAI strict structured outputs** — generates JSON Schema with `additionalProperties: false` for deterministic results
- **PHP 8 Attributes** — mark properties with `#[JsonFicatorDescription]`, `#[JsonFicatorRequired]`, `#[JsonFicatorOptional]`
- **Immutable result** — `JsonFicatorResult` with `toArray()`, `toJson()`, `toModel()`
- **Per-call options** — `JsonFicatorOptions` with `withModel()`, `withTemperature()`, `withMaxTokens()`
- **Extensible** — swap LLM provider via `LLMProviderInterface`
- **Modern PHP** — `declare(strict_types=1)`, readonly properties, named arguments, PHP 8.2+

## Installation

```bash
composer require jsonficator/jsonficator
```

You also need a PSR-18 HTTP client (e.g. Guzzle) in your project:

```bash
composer require guzzlehttp/guzzle
```

## Quick Start

### Array schema

```php
<?php

use JsonFicator\JsonFicator;
use JsonFicator\Provider\OpenAIProvider;

$jsonFicator = new JsonFicator(
    new OpenAIProvider(['key' => 'sk-...'])
);

$result = $jsonFicator->toJson(
    'My car is a red Toyota Camry from 2020.',
    ['brand' => 'string', 'model' => 'string', 'year' => 'int', 'color' => '?string']
);

print_r($result->toArray());
// ['brand' => 'Toyota', 'model' => 'Camry', 'year' => 2020, 'color' => 'red']
```

### PHP model schema

```php
<?php

use JsonFicator\IJsonFicatorModel;
use JsonFicator\Attribute\JsonFicatorDescription;
use JsonFicator\Attribute\JsonFicatorRequired;

final class Car implements IJsonFicatorModel
{
    #[JsonFicatorDescription('Vehicle manufacturer')]
    public string $brand;

    #[JsonFicatorDescription('Specific model name')]
    public string $model;

    #[JsonFicatorRequired]
    public int $year;

    #[JsonFicatorDescription('Optional paint color')]
    public ?string $color = null;
}

$jsonFicator = new JsonFicator(
    new OpenAIProvider(['key' => 'sk-...'])
);

$result = $jsonFicator->toModel(
    'My car is a red Toyota Camry from 2020.',
    Car::class
);

$car = $result->toModel(); // instance of Car
```

### Nested models

```php
<?php

use JsonFicator\IJsonFicatorModel;

final class Owner implements IJsonFicatorModel
{
    public string $name;
    public ?string $phone = null;
}

final class Car implements IJsonFicatorModel
{
    public string $brand;
    public Owner $owner;
}

$result = $jsonFicator->toModel(
    'John owns a Ford. His phone is +1-555-1234.',
    Car::class
);
```

## Configuration

### OpenAIProvider

```php
$provider = new OpenAIProvider([
    'host'   => 'https://api.openai.com/v1', // or any OpenAI-compatible endpoint
    'key'    => 'sk-...',                    // or set OPENAI_API_KEY env var
    'model'  => 'gpt-4o',
    'strict' => true,                        // JSON Schema strict mode
]);
```

### Per-call options

```php
use JsonFicator\JsonFicatorOptions;

$options = (new JsonFicatorOptions())
    ->withModel('gpt-4o-mini')
    ->withTemperature(0.1)
    ->withMaxTokens(512);

$result = $jsonFicator->toJson($text, $schema, $options);
```

## Schema Syntax (Array)

| Syntax        | Meaning                          |
|---------------|----------------------------------|
| `string`      | Non-nullable string              |
| `?string`     | Nullable string                  |
| `int`         | Integer                          |
| `float`       | Float / double                   |
| `bool`        | Boolean                          |
| `array`       | Untyped array                    |
| `array<string>` | Array of strings               |
| `array<int>`  | Array of integers                |
| `array<array>`| Nested array of objects          |

## Attributes

- `#[JsonFicatorDescription('...')]` — adds description to the JSON Schema field
- `#[JsonFicatorRequired]` — marks property as required (default for non-nullable)
- `#[JsonFicatorOptional]` — marks property as optional even if type is non-nullable

## Testing

```bash
# Unit tests only
composer run test:unit

# Integration tests (requires JSONFICATOR_OPENAI_API_KEY)
composer run test:integration

# All tests
composer run test
```

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for design decisions and internal pipeline.

## License

MIT
