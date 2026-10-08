<?php

declare(strict_types=1);

namespace JsonFicator\Tests\Unit;

use JsonFicator\Exception\InvalidResponseException;
use JsonFicator\Response\ResponseParser;
use PHPUnit\Framework\TestCase;

final class ResponseParserTest extends TestCase
{
    private ResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ResponseParser();
    }

    public function testParsesArray(): void
    {
        $data = ['brand' => 'Nissan'];
        $this->assertSame($data, $this->parser->parse($data));
    }

    public function testParsesJsonString(): void
    {
        $json = '{"brand":"Nissan","model":"Leaf"}';
        $expected = ['brand' => 'Nissan', 'model' => 'Leaf'];

        $this->assertSame($expected, $this->parser->parse($json));
    }

    public function testParsesWithCodeFence(): void
    {
        $json = "```json\n{\"brand\":\"Nissan\"}\n```";
        $this->assertSame(['brand' => 'Nissan'], $this->parser->parse($json));
    }

    public function testParsesWithPlainCodeFence(): void
    {
        $json = "```\n{\"brand\":\"Nissan\"}\n```";
        $this->assertSame(['brand' => 'Nissan'], $this->parser->parse($json));
    }

    public function testThrowsOnInvalidJson(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->parser->parse('not json');
    }

    public function testThrowsOnJsonArray(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->parser->parse('[1, 2, 3]');
    }

    public function testThrowsOnNonStringNonArray(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->parser->parse(42);
    }
}
