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
        // Temporarily remove env key so the constructor has nothing to fall back to.
        $original = $_SERVER['JSONFICATOR_OPENAI_API_KEY'] ?? null;
        unset($_SERVER['JSONFICATOR_OPENAI_API_KEY'], $_ENV['JSONFICATOR_OPENAI_API_KEY']);

        try {
            $this->expectException(ProviderException::class);
            $this->expectExceptionMessage('OpenAI API key is required');

            new OpenAIProvider();
        } finally {
            if ($original !== null) {
                $_SERVER['JSONFICATOR_OPENAI_API_KEY'] = $original;
                $_ENV['JSONFICATOR_OPENAI_API_KEY'] = $original;
            }
        }
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
