<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\ProviderException;
use JsonFicator\Provider\OpenAIProvider;
use PHPUnit\Framework\TestCase;

final class OpenAIProviderTest extends TestCase
{
    public function testThrowsWithoutApiKey(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('OpenAI API key is required');

        new OpenAIProvider();
    }

    public function testThrowsWithEmptyApiKey(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('OpenAI API key is required');

        new OpenAIProvider(['key' => '']);
    }

    public function testAcceptsApiKeyFromConfig(): void
    {
        // We cannot make a real request, but we can at least verify the object is created.
        $provider = new OpenAIProvider([
            'key' => 'sk-test',
            'model' => 'gpt-4o-mini',
        ]);

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    public function testAcceptsApiKeyFromEnvironment(): void
    {
        $_SERVER['JSONFICATOR_OPENAI_API_KEY'] = 'sk-env';

        try {
            $provider = new OpenAIProvider();
            $this->assertInstanceOf(OpenAIProvider::class, $provider);
        } finally {
            unset($_SERVER['JSONFICATOR_OPENAI_API_KEY']);
        }
    }
}
