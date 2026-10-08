<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Integration;

use JsonFicator\JsonFicator;
use JsonFicator\Provider\OpenAIProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('integration')]
/**
 * These tests require:
 * - JSONFICATOR_OPENAI_API_KEY environment variable
 * - JSONFICATOR_OPENAI_HOST environment variable (optional, defaults to https://api.openai.com/v1)
 *
 * You can set them in a .env file in the project root (not committed to git).
 * Run them with: composer test:integration
 */
final class OpenAIIntegrationTest extends TestCase
{
    private static ?string $apiKey = null;
    private static ?string $host = null;
    private static ?string $model = null;

    public static function setUpBeforeClass(): void
    {
        self::$apiKey = $_SERVER['JSONFICATOR_OPENAI_API_KEY'] ?? getenv('JSONFICATOR_OPENAI_API_KEY') ?: null;
        self::$host = $_SERVER['JSONFICATOR_OPENAI_HOST'] ?? getenv('JSONFICATOR_OPENAI_HOST') ?: 'https://api.openai.com/v1';
        self::$model = $_SERVER['JSONFICATOR_OPENAI_MODEL'] ?? getenv('JSONFICATOR_OPENAI_MODEL') ?: 'gpt-4o-mini';
    }

    protected function setUp(): void
    {
        if (self::$apiKey === null || self::$apiKey === '') {
            $this->markTestSkipped('JSONFICATOR_OPENAI_API_KEY environment variable is not set.');
        }
    }

    public function testToJsonExtractsData(): void
    {
        $provider = new OpenAIProvider([
            'host' => self::$host,
            'key' => self::$apiKey,
            'model' => self::$model,
        ]);

        $jsonFicator = new JsonFicator($provider);

        $result = $jsonFicator->toJson('Колодки на ниссан лиф +799999999', [
            'carBrand' => 'string?',
            'carModel' => 'string?',
            'phoneNumber' => 'string?',
        ]);

        $data = $result->toArray();

        $this->assertArrayHasKey('carBrand', $data);
        $this->assertArrayHasKey('carModel', $data);
        $this->assertArrayHasKey('phoneNumber', $data);

        // Brand should be detected (Nissan).
        $this->assertNotNull($data['carBrand']);
        $this->assertIsString($data['carBrand']);
    }

    public function testToJsonDoesNotInventValues(): void
    {
        $provider = new OpenAIProvider([
            'host' => self::$host,
            'key' => self::$apiKey,
            'model' => self::$model,
        ]);

        $jsonFicator = new JsonFicator($provider);

        $result = $jsonFicator->toJson('Колодки на Nissan Leaf', [
            'brand' => 'string',
            'model' => 'string',
            'year' => 'int?',
            'phone' => 'string?',
        ]);

        $data = $result->toArray();

        $this->assertSame('Nissan', $data['brand']);
        $this->assertSame('Leaf', $data['model']);

        // Year and phone are not present in the text and are nullable.
        $this->assertNull($data['year']);
        $this->assertNull($data['phone']);
    }
}
