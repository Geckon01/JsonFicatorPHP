<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\SchemaException;
use JsonFicator\Schema\ArraySchemaResolver;
use JsonFicator\Schema\SchemaField;
use PHPUnit\Framework\TestCase;

final class ArraySchemaResolverTest extends TestCase
{
    private ArraySchemaResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArraySchemaResolver();
    }

    public function testResolvesPrimitives(): void
    {
        $schema = $this->resolver->resolve([
            'name' => 'string',
            'age' => 'int',
            'price' => 'float',
            'active' => 'bool',
        ]);

        $this->assertTrue($schema->hasField('name'));
        $this->assertSame('string', $schema->getField('name')?->type);
        $this->assertFalse($schema->getField('name')?->nullable);
        $this->assertTrue($schema->getField('name')?->required);

        $this->assertSame('int', $schema->getField('age')?->type);
        $this->assertSame('float', $schema->getField('price')?->type);
        $this->assertSame('bool', $schema->getField('active')?->type);
    }

    public function testResolvesNullablePrimitives(): void
    {
        $schema = $this->resolver->resolve([
            'name' => 'string?',
            'age' => 'int?',
            'price' => 'float?',
            'active' => 'bool?',
        ]);

        $this->assertTrue($schema->getField('name')?->nullable);
        $this->assertFalse($schema->getField('name')?->required);
    }

    public function testResolvesNestedObject(): void
    {
        $schema = $this->resolver->resolve([
            'car' => [
                'brand' => 'string',
                'model' => 'string?',
            ],
        ]);

        $car = $schema->getField('car');
        $this->assertNotNull($car);
        $this->assertSame('object', $car->type);
        $this->assertSame('string', $car->properties['brand']?->type);
        $this->assertTrue($car->properties['brand']?->required);
        $this->assertTrue($car->properties['model']?->nullable);
    }

    public function testResolvesTypedArray(): void
    {
        $schema = $this->resolver->resolve([
            'tags' => 'array<string>',
        ]);

        $field = $schema->getField('tags');
        $this->assertNotNull($field);
        $this->assertSame('array', $field->type);
        $this->assertNotNull($field->items);
        $this->assertSame('string', $field->items->type);
    }

    public function testResolvesGenericArray(): void
    {
        $schema = $this->resolver->resolve([
            'items' => 'array',
        ]);

        $this->assertSame('array', $schema->getField('items')?->type);
        $this->assertNull($schema->getField('items')?->items);
    }

    public function testThrowsOnUnknownType(): void
    {
        $this->expectException(SchemaException::class);
        $this->resolver->resolve(['foo' => 'unknown']);
    }

    public function testThrowsOnEmptyFieldName(): void
    {
        $this->expectException(SchemaException::class);
        $this->resolver->resolve(['' => 'string']);
    }

    public function testThrowsOnEmptyType(): void
    {
        $this->expectException(SchemaException::class);
        $this->resolver->resolve(['foo' => '']);
    }

    public function testThrowsOnNonArrayDefinition(): void
    {
        $this->expectException(SchemaException::class);
        $this->resolver->resolve('not-an-array');
    }

    public function testDeepNesting(): void
    {
        $schema = $this->resolver->resolve([
            'level1' => [
                'level2' => [
                    'value' => 'int',
                ],
            ],
        ]);

        $l1 = $schema->getField('level1');
        $this->assertNotNull($l1);
        $l2 = $l1->properties['level2'] ?? null;
        $this->assertNotNull($l2);
        $this->assertSame('object', $l2->type);
        $this->assertSame('int', $l2->properties['value']?->type);
    }
}
