<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\ModelHydrationException;
use JsonFicator\Response\ModelHydrator;
use JsonFicator\Tests\Fixtures\Car;
use JsonFicator\Tests\Fixtures\NestedRequest;
use JsonFicator\Tests\Fixtures\VinRequest;
use PHPUnit\Framework\TestCase;

final class HydrationTest extends TestCase
{
    private ModelHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new ModelHydrator();
    }

    public function testHydratesSimpleModel(): void
    {
        $data = [
            'brand' => 'Nissan',
            'model' => 'Leaf',
            'phone' => '+799999999',
        ];

        /** @var VinRequest $result */
        $result = $this->hydrator->hydrate($data, VinRequest::class);

        $this->assertInstanceOf(VinRequest::class, $result);
        $this->assertSame('Nissan', $result->brand);
        $this->assertSame('Leaf', $result->model);
        $this->assertSame('+799999999', $result->phone);
    }

    public function testHydratesNullableAsNull(): void
    {
        $data = [
            'brand' => 'Nissan',
            'model' => null,
            'phone' => null,
        ];

        /** @var VinRequest $result */
        $result = $this->hydrator->hydrate($data, VinRequest::class);

        $this->assertNull($result->model);
        $this->assertNull($result->phone);
    }

    public function testHydratesNestedModel(): void
    {
        $data = [
            'car' => [
                'brand' => 'Nissan',
                'model' => 'Leaf',
            ],
            'phone' => null,
        ];

        /** @var NestedRequest $result */
        $result = $this->hydrator->hydrate($data, NestedRequest::class);

        $this->assertInstanceOf(NestedRequest::class, $result);
        $this->assertInstanceOf(Car::class, $result->car);
        $this->assertSame('Nissan', $result->car->brand);
        $this->assertSame('Leaf', $result->car->model);
        $this->assertNull($result->phone);
    }

    public function testThrowsOnMissingProperty(): void
    {
        $this->expectException(ModelHydrationException::class);

        $this->hydrator->hydrate(['unknown' => 'value'], VinRequest::class);
    }

    public function testThrowsOnTypeMismatch(): void
    {
        $this->expectException(ModelHydrationException::class);

        $this->hydrator->hydrate(['brand' => ['invalid']], VinRequest::class);
    }

    public function testCastsIntToStringForNullableString(): void
    {
        $data = [
            'brand' => 'Nissan',
            'model' => 2023,
            'phone' => null,
        ];

        /** @var VinRequest $result */
        $result = $this->hydrator->hydrate($data, VinRequest::class);

        $this->assertSame('2023', $result->model);
    }
}
