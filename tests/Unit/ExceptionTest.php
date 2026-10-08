<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\InvalidResponseException;
use JsonFicator\Exception\JsonFicatorException;
use JsonFicator\Exception\ModelHydrationException;
use JsonFicator\Exception\ProviderException;
use JsonFicator\Exception\SchemaException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $this->assertTrue(is_subclass_of(SchemaException::class, JsonFicatorException::class));
        $this->assertTrue(is_subclass_of(ProviderException::class, JsonFicatorException::class));
        $this->assertTrue(is_subclass_of(InvalidResponseException::class, JsonFicatorException::class));
        $this->assertTrue(is_subclass_of(ModelHydrationException::class, JsonFicatorException::class));
    }

    public function testSchemaExceptionCarriesMessage(): void
    {
        $e = new SchemaException('Invalid type');
        $this->assertSame('Invalid type', $e->getMessage());
    }

    public function testProviderExceptionCarriesPrevious(): void
    {
        $previous = new \RuntimeException('Network error');
        $e = new ProviderException('API failed', previous: $previous);

        $this->assertSame('API failed', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testInvalidResponseExceptionCarriesErrors(): void
    {
        $errors = ['Field x is missing'];
        $e = new InvalidResponseException('Validation failed', $errors);

        $this->assertSame('Validation failed', $e->getMessage());
    }

    public function testModelHydrationExceptionCarriesMessage(): void
    {
        $e = new ModelHydrationException('Property foo not found');
        $this->assertSame('Property foo not found', $e->getMessage());
    }
}
