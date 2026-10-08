<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Fixtures;

use JsonFicator\IJsonFicatorModel;

class Car implements IJsonFicatorModel
{
    public string $brand;
    public string $model;
}
