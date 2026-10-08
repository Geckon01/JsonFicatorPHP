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

final class ValidatorTest extends TestCase
{
    private Validator $validator;
    private ArraySchemaResolver $arrayResolver;

    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->arrayResolver = new ArraySchemaResolver();
    }

    public function testValidatesPrimitives(): void
    {
        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
            'year' => 'int',
            'price' => 'float',
            'active' => 'bool',
        ]);

        $this->validator->validate([
            'brand' => 'Nissan',
            'year' => 2023,
            'price' => 12.5,
            'active' => true,
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testValidatesNullable(): void
    {
        $schema = $this->arrayResolver->resolve([
            'phone' => 'string?',
        ]);

        $this->validator->validate(['phone' => null], $schema);
        $this->addToAssertionCount(1);
    }

    public function testThrowsOnNullForNonNullable(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
        ]);

        $this->validator->validate(['brand' => null], $schema);
    }

    public function testThrowsOnMissingRequired(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
        ]);

        $this->validator->validate([], $schema);
    }

    public function testThrowsOnWrongType(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'year' => 'int',
        ]);

        $this->validator->validate(['year' => 'not-int'], $schema);
    }

    public function testThrowsOnUnexpectedField(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
        ]);

        $this->validator->validate(['brand' => 'Nissan', 'extra' => 'value'], $schema);
    }

    public function testValidatesNestedObject(): void
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
                'model' => null,
            ],
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testThrowsOnUnexpectedNestedField(): void
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

    public function testValidatesArrayItems(): void
    {
        $schema = $this->arrayResolver->resolve([
            'tags' => 'array<string>',
        ]);

        $this->validator->validate(['tags' => ['a', 'b']], $schema);
        $this->addToAssertionCount(1);
    }

    public function testThrowsOnArrayItemTypeMismatch(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'tags' => 'array<int>',
        ]);

        $this->validator->validate(['tags' => [1, 'not-int']], $schema);
    }

    public function testValidatesClassSchema(): void
    {
        $resolver = new ClassSchemaResolver();
        $schema = $resolver->resolve(NestedRequest::class);

        $this->validator->validate([
            'car' => [
                'brand' => 'Nissan',
                'model' => 'Leaf',
            ],
            'phone' => null,
        ], $schema);

        $this->addToAssertionCount(1);
    }
}
