<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Fixtures;

use JsonFicator\Attribute\JsonFicatorDescription;
use JsonFicator\Attribute\JsonFicatorOptional;
use JsonFicator\Attribute\JsonFicatorRequired;
use JsonFicator\IJsonFicatorModel;

class DescribedModel implements IJsonFicatorModel
{
    #[JsonFicatorDescription('Марка автомобиля')]
    public ?string $brand;

    #[JsonFicatorRequired]
    public ?string $requiredNullable;

    #[JsonFicatorOptional]
    public string $optionalNonNullable;
}
