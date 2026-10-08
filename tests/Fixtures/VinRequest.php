<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Fixtures;

use JsonFicator\IJsonFicatorModel;

class VinRequest implements IJsonFicatorModel
{
    public ?string $model;
    public ?string $brand;
    public ?string $phone;
}
