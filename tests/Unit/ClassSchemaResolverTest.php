<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\SchemaException;
use JsonFicator\Schema\ClassSchemaResolver;
use JsonFicator\Tests\Fixtures\Car;
use JsonFicator\Tests\Fixtures\DescribedModel;
use JsonFicator\Tests\Fixtures\NestedRequest;
use JsonFicator\Tests\Fixtures\VinRequest;
use PHPUnit\Framework\TestCase;

final class ClassSchemaResolverTest extends TestCase
{
    private ClassSchemaResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ClassSchemaResolver();
    }

    public function testResolvesSimpleModel(): void
    {
        $schema = $this->resolver->resolve(VinRequest::class);

        $this->assertSame(VinRequest::class, $schema->modelClass);
        $this->assertTrue($schema->hasField('model'));
        $this->assertSame('string', $schema->getField('model')?->type);
        $this->assertTrue($schema->getField('model')?->nullable);
        $this->assertFalse($schema->getField('model')?->required);
    }

    public function testResolvesNestedModel(): void
    {
        $schema = $this->resolver->resolve(NestedRequest::class);

        $car = $schema->getField('car');
        $this->assertNotNull($car);
        $this->assertSame('object', $car->type);
        $this->assertSame(Car::class, $car->class);
        $this->assertTrue($car->required);

        $this->assertSame('string', $car->properties['brand']?->type);
        $this->assertTrue($car->properties['brand']?->required);
        $this->assertFalse($car->properties['brand']?->nullable);
    }

    public function testResolvesAttributes(): void
    {
        $schema = $this->resolver->resolve(DescribedModel::class);

        $brand = $schema->getField('brand');
        $this->assertNotNull($brand);
        $this->assertSame('Марка автомобиля', $brand->description);

        $required = $schema->getField('requiredNullable');
        $this->assertNotNull($required);
        $this->assertTrue($required->nullable);
        $this->assertTrue($required->required);

        $optional = $schema->getField('optionalNonNullable');
        $this->assertNotNull($optional);
        $this->assertFalse($optional->nullable);
        $this->assertFalse($optional->required);
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(SchemaException::class);
        $this->resolver->resolve('NonExistentClass');
    }

    public function testRespectsRequireInterfaceFlag(): void
    {
        // ClassSchemaResolver with requireInterface=false should accept any class
        $resolver = new ClassSchemaResolver(requireInterface: false);

        $stdClass = new class {
            public string $name;
        };

        $schema = $resolver->resolve($stdClass::class);
        $this->assertTrue($schema->hasField('name'));
        $this->assertSame('string', $schema->getField('name')?->type);
    }
}
