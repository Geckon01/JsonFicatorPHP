<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\InvalidResponseException;
use JsonFicator\Response\Validator;
use JsonFicator\Schema\ArraySchemaResolver;
use JsonFicator\Schema\ClassSchemaResolver;
use JsonFicator\Schema\JsonSchemaConverter;
use JsonFicator\Tests\Fixtures\NestedRequest;
use PHPUnit\Framework\TestCase;

final class NestedSchemaTest extends TestCase
{
    private ArraySchemaResolver $arrayResolver;
    private ClassSchemaResolver $classResolver;
    private JsonSchemaConverter $converter;
    private Validator $validator;

    protected function setUp(): void
    {
        $this->arrayResolver = new ArraySchemaResolver();
        $this->classResolver = new ClassSchemaResolver();
        $this->converter = new JsonSchemaConverter();
        $this->validator = new Validator();
    }

    public function testArrayNestedObjectSchema(): void
    {
        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
                'model' => 'string?',
            ],
            'phone' => 'string?',
        ]);

        $this->assertTrue($schema->hasField('car'));
        $this->assertSame('object', $schema->getField('car')?->type);
        $this->assertTrue($schema->getField('car')?->required);
    }

    public function testArrayNestedObjectJsonSchema(): void
    {
        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
                'model' => 'string?',
            ],
        ]);

        $jsonSchema = $this->converter->convert($schema);

        $this->assertSame('object', $jsonSchema['properties']['car']['type']);
        $this->assertArrayHasKey('properties', $jsonSchema['properties']['car']);
        $this->assertSame(['brand'], $jsonSchema['properties']['car']['required']);
        $this->assertFalse($jsonSchema['properties']['car']['additionalProperties']);
    }

    public function testClassNestedObjectSchema(): void
    {
        $schema = $this->classResolver->resolve(NestedRequest::class);

        $this->assertTrue($schema->hasField('car'));
        $this->assertSame('object', $schema->getField('car')?->type);
        $this->assertSame(\JsonFicator\Tests\Fixtures\Car::class, $schema->getField('car')?->class);
    }

    public function testDeepArrayNesting(): void
    {
        $schema = $this->arrayResolver->resolve([
            'level1' => [
                'level2' => [
                    'value' => 'int',
                ],
            ],
        ]);

        $level1 = $schema->getField('level1');
        $this->assertNotNull($level1);
        $level2 = $level1->properties['level2'] ?? null;
        $this->assertNotNull($level2);
        $this->assertSame('int', $level2->properties['value']?->type);
    }

    public function testNestedValidationSuccess(): void
    {
        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
                'model' => 'string?',
            ],
        ]);

        $this->validator->validate([
            'car' => [
                'brand' => 'Nissan',
                'model' => 'Leaf',
            ],
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testNestedValidationFailsOnMissingRequiredChild(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
            ],
        ]);

        $this->validator->validate([
            'car' => [
                'model' => 'Leaf',
            ],
        ], $schema);
    }

    public function testNestedValidationFailsOnUnexpectedChildField(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'car' => [
                'brand' => 'string',
            ],
        ]);

        $this->validator->validate([
            'car' => [
                'brand' => 'Nissan',
                'extra' => 'value',
            ],
        ], $schema);
    }

    public function testTypedArrayOfObjects(): void
    {
        $schema = $this->arrayResolver->resolve([
            'cars' => 'array<object>',
        ]);

        $cars = $schema->getField('cars');
        $this->assertNotNull($cars);
        $this->assertSame('array', $cars->type);
        $this->assertSame('object', $cars->items?->type);
    }
}
