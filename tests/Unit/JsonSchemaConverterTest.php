<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Schema\ArraySchemaResolver;
use JsonFicator\Schema\ClassSchemaResolver;
use JsonFicator\Schema\JsonSchemaConverter;
use JsonFicator\Tests\Fixtures\NestedRequest;
use PHPUnit\Framework\TestCase;

final class JsonSchemaConverterTest extends TestCase
{
    private JsonSchemaConverter $converter;
    private ArraySchemaResolver $arrayResolver;
    private ClassSchemaResolver $classResolver;

    protected function setUp(): void
    {
        $this->converter = new JsonSchemaConverter();
        $this->arrayResolver = new ArraySchemaResolver();
        $this->classResolver = new ClassSchemaResolver();
    }

    public function testConvertsPrimitives(): void
    {
        $schema = $this->arrayResolver->resolve([
            'name' => 'string',
            'age' => 'int',
            'price' => 'float',
            'active' => 'bool',
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertSame('object', $jsonSchema['type']);
        $this->assertSame('string', $jsonSchema['properties']['name']['type']);
        $this->assertSame('integer', $jsonSchema['properties']['age']['type']);
        $this->assertSame('number', $jsonSchema['properties']['price']['type']);
        $this->assertSame('boolean', $jsonSchema['properties']['active']['type']);
    }

    public function testConvertsNullable(): void
    {
        $schema = $this->arrayResolver->resolve([
            'name' => 'string?',
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertArrayHasKey('anyOf', $jsonSchema['properties']['name']);
        $this->assertCount(2, $jsonSchema['properties']['name']['anyOf']);
        $this->assertSame('null', $jsonSchema['properties']['name']['anyOf'][1]['type']);
    }

    public function testRequiredList(): void
    {
        $schema = $this->arrayResolver->resolve([
            'name' => 'string',
            'age' => 'int?',
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertSame(['name'], $jsonSchema['required']);
    }

    public function testAdditionalPropertiesFalse(): void
    {
        $schema = $this->arrayResolver->resolve([
            'name' => 'string',
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertFalse($jsonSchema['additionalProperties']);
    }

    public function testConvertsNestedObject(): void
    {
        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
                'model' => 'string?',
            ],
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $car = $jsonSchema['properties']['car'];
        $this->assertSame('object', $car['type']);
        $this->assertSame('string', $car['properties']['brand']['type']);
        $this->assertArrayHasKey('anyOf', $car['properties']['model']);
        $this->assertSame(['brand'], $car['required']);
        $this->assertFalse($car['additionalProperties']);
    }

    public function testConvertsArray(): void
    {
        $schema = $this->arrayResolver->resolve([
            'tags' => 'array<string>',
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertSame('array', $jsonSchema['properties']['tags']['type']);
        $this->assertSame('string', $jsonSchema['properties']['tags']['items']['type']);
    }

    public function testConvertsClassSchema(): void
    {
        $schema = $this->classResolver->resolve(NestedRequest::class);
        $jsonSchema = $this->converter->convert($schema);

        $this->assertSame('object', $jsonSchema['type']);
        $this->assertSame('object', $jsonSchema['properties']['car']['type']);
        $this->assertSame('string', $jsonSchema['properties']['car']['properties']['brand']['type']);
        $this->assertArrayHasKey('anyOf', $jsonSchema['properties']['phone']);
    }

    public function testNonStrictMode(): void
    {
        $converter = new JsonSchemaConverter(strict: false);
        $schema = $this->arrayResolver->resolve([
            'name' => 'string',
        ]);

        $jsonSchema = $converter->convert($schema);

        $this->assertArrayNotHasKey('additionalProperties', $jsonSchema);
        $this->assertArrayNotHasKey('required', $jsonSchema);
    }
}
