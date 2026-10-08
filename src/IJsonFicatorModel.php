<?php

declare(strict_types=1);

namespace JsonFicator;

/**
 * Marker interface for classes that can be used as a JsonFicator model.
 *
 * Implementing this interface signals that the class is intended to be
 * hydrated from extracted data. The library inspects the class via
 * reflection to build a schema and to hydrate instances.
 *
 * The interface is intentionally empty so that models remain plain data
 * holders. Optional behaviour (descriptions, required/optional) is provided
 * via attributes in the {@see \JsonFicator\Attribute} namespace.
 */
interface IJsonFicatorModel
{
}