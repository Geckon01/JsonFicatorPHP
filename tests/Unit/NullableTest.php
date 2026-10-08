<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\InvalidResponseException;
use JsonFicator\Response\Validator;
use JsonFicator\Schema\ArraySchemaResolver;
use JsonFicator\Schema\ClassSchemaResolver;
use JsonFicator\Tests\Fixtures\VinRequest;
use PHPUnit\Framework\TestCase;

final class NullableTest extends TestCase
{
    private ArraySchemaResolver $arrayResolver;
    private ClassSchemaResolver $classResolver;
    private Validator $validator;

    protected function setUp(): void
    {
        $this->arrayResolver = new ArraySchemaResolver();
        $this->classResolver = new ClassSchemaResolver();
        $this->validator = new Validator();
    }

    public function testNullableArrayFieldAcceptsNull(): void
    {
        $schema = $this->arrayResolver->resolve([
            'year' => 'int?',
            'phone' => 'string?',
        ]);

        $this->validator->validate([
            'year' => null,
            'phone' => null,
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testNullableArrayFieldAcceptsValue(): void
    {
        $schema = $this->arrayResolver->resolve([
            'year' => 'int?',
        ]);

        $this->validator->validate(['year' => 2023], $schema);
        $this->addToAssertionCount(1);
    }

    public function testNonNullableArrayFieldRejectsNull(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
        ]);

        $this->validator->validate(['brand' => null], $schema);
    }

    public function testNullableClassFieldAcceptsNull(): void
    {
        $schema = $this->classResolver->resolve(VinRequest::class);

        $this->validator->validate([
            'model' => null,
            'brand' => null,
            'phone' => null,
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testNullableClassFieldAcceptsValue(): void
    {
        $schema = $this->classResolver->resolve(VinRequest::class);

        $this->validator->validate([
            'model' => 'Leaf',
            'brand' => 'Nissan',
            'phone' => '+799999999',
        ], $schema);

        $this->addToAssertionCount(1);
    }

    public function testNullableArrayFieldIsOptional(): void
    {
        $schema = $this->arrayResolver->resolve([
            'year' => 'int?',
        ]);

        $this->validator->validate([], $schema);
        $this->addToAssertionCount(1);
    }

    public function testNonNullableArrayFieldIsRequired(): void
    {
        $this->expectException(InvalidResponseException::class);

        $schema = $this->arrayResolver->resolve([
            'brand' => 'string',
        ]);

        $this->validator->validate([], $schema);
    }
}
