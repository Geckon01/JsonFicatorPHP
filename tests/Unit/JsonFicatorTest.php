<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\JsonFicator;
use JsonFicator\Tests\Fixtures\Car;
use JsonFicator\Tests\Fixtures\FakeProvider;
use JsonFicator\Tests\Fixtures\NestedRequest;
use JsonFicator\Tests\Fixtures\VinRequest;
use PHPUnit\Framework\TestCase;

final class JsonFicatorTest extends TestCase
{
    public function testToJsonReturnsArrayResult(): void
    {
        $provider = new FakeProvider([
            'carBrand' => 'Nissan',
            'carModel' => 'Leaf',
            'phoneNumber' => '+799999999',
        ]);

        $jsonFicator = new JsonFicator($provider);

        $result = $jsonFicator->toJson('Brake pads for Nissan Leaf +799999999', [
            'carBrand' => 'string?',
            'carModel' => 'string?',
            'phoneNumber' => 'string',
        ]);

        $this->assertSame('Nissan', $result->get('carBrand'));
        $this->assertSame('Leaf', $result->get('carModel'));
        $this->assertSame('+799999999', $result->get('phoneNumber'));
        $this->assertSame(
            '{"carBrand":"Nissan","carModel":"Leaf","phoneNumber":"+799999999"}',
            $result->toJson()
        );
    }

    public function testToModelReturnsHydratedInstance(): void
    {
        $provider = new FakeProvider([
            'model' => 'Leaf',
            'brand' => 'Nissan',
            'phone' => '+799999999',
        ]);

        $jsonFicator = new JsonFicator($provider);

        /** @var VinRequest $result */
        $result = $jsonFicator->toModel('Brake pads for Nissan Leaf +799999999', VinRequest::class);

        $this->assertInstanceOf(VinRequest::class, $result);
        $this->assertSame('Nissan', $result->brand);
        $this->assertSame('Leaf', $result->model);
        $this->assertSame('+799999999', $result->phone);
    }

    public function testToModelWithNestedClass(): void
    {
        $provider = new FakeProvider([
            'car' => [
                'brand' => 'Nissan',
                'model' => 'Leaf',
            ],
            'phone' => null,
        ]);

        $jsonFicator = new JsonFicator($provider);

        /** @var NestedRequest $result */
        $result = $jsonFicator->toModel('Brake pads for Nissan Leaf', NestedRequest::class);

        $this->assertInstanceOf(NestedRequest::class, $result);
        $this->assertInstanceOf(Car::class, $result->car);
        $this->assertSame('Nissan', $result->car->brand);
        $this->assertSame('Leaf', $result->car->model);
        $this->assertNull($result->phone);
    }

    public function testToJsonWithNullable(): void
    {
        $provider = new FakeProvider([
            'brand' => 'Nissan',
            'model' => 'Leaf',
            'year' => null,
            'phone' => null,
        ]);

        $jsonFicator = new JsonFicator($provider);

        $result = $jsonFicator->toJson('Brake pads for Nissan Leaf', [
            'brand' => 'string',
            'model' => 'string',
            'year' => 'int?',
            'phone' => 'string?',
        ]);

        $this->assertNull($result->get('year'));
        $this->assertNull($result->get('phone'));
    }

    public function testResultToArray(): void
    {
        $provider = new FakeProvider(['key' => 'value']);
        $jsonFicator = new JsonFicator($provider);

        $result = $jsonFicator->toJson('input', ['key' => 'string']);

        $this->assertSame(['key' => 'value'], $result->toArray());
    }
}
