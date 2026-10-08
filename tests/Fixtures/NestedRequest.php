<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Fixtures;

use JsonFicator\IJsonFicatorModel;

class NestedRequest implements IJsonFicatorModel
{
    public Car $car;
    public ?string $phone;
}
