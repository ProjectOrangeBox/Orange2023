<?php

declare(strict_types=1);

use dmyers\orange\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new Input([
            'raw' => [],
            'file' => [],
            'server' => [
                'request_uri' => '/product/123abc',
                'request_method' => 'get',
                'http_x_requested_with' => 'xmlhttprequest',
                'http_accept' => 'application/json',
                'https' => true,
            ],
            'post' => [
                'name' => 'Johnny Appleseed',
                'age' => 25,
            ],
            'get' => [
                'name' => 'Johnny Appleseed',
                'age' => 26,
            ],
            'request' => [
                'name' => 'Johnny Appleseed',
                'age' => 27,
            ],
            'cookie' => [
                'name' => 'Johnny Appleseed',
                'age' => 28,
            ],
        ]);
    }

    // Tests
    public function testRequestUri(): void
    {
        $this->assertEquals('/product/123abc', $this->instance->requestUri());
    }

    public function testUriSegement(): void
    {
        $this->assertEquals('product', $this->instance->uriSegement(1));
        $this->assertEquals('123abc', $this->instance->uriSegement(2));
    }

    public function testRequestMethod(): void
    {
        $this->assertEquals('cli', $this->instance->requestMethod());
    }

    public function testRequestType(): void
    {
        // because of config sent in
        $this->assertEquals('ajax', $this->instance->requestType());
    }

    public function testIsAjaxRequest(): void
    {
        $this->assertTrue($this->instance->isAjaxRequest());
    }

    public function testIsCliRequest(): void
    {
        // because of config sent in
        $this->assertFalse($this->instance->isCliRequest());
    }

    public function testIsHttpsRequest(): void
    {
        $this->assertTrue($this->instance->isHttpsRequest());
    }

    public function testRaw(): void
    {
        $this->assertEquals([], $this->instance->raw());
    }

    public function testPost(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 25,
        ], $this->instance->post());
    }

    public function testGet(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 26,
        ], $this->instance->get());
    }

    public function testRequest(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 27,
        ], $this->instance->request());
    }

    public function testServer(): void
    {
        $this->assertEquals([
            'request_uri' => '/product/123abc',
            'request_method' => 'get',
            'http_x_requested_with' => 'xmlhttprequest',
            'http_accept' => 'application/json',
            'https' => true,

        ], $this->instance->server());
    }

    public function testFile(): void
    {
        $this->assertEquals([], $this->instance->file());
    }

    public function testCopy(): void
    {
        $this->assertEquals([
            'raw' => [],
            'file' => [],
            'server' => [
                'request_uri' => '/product/123abc',
                'request_method' => 'get',
                'http_x_requested_with' => 'xmlhttprequest',
                'http_accept' => 'application/json',
                'https' => true,
            ],
            'post' => [
                'name' => 'Johnny Appleseed',
                'age' => 25,
            ],
            'get' => [
                'name' => 'Johnny Appleseed',
                'age' => 26,
            ],
            'request' => [
                'name' => 'Johnny Appleseed',
                'age' => 27,
            ],
            'cookie' => [
                'name' => 'Johnny Appleseed',
                'age' => 28,
            ],
        ], $this->instance->copy());
    }

    public function testReplace(): void
    {
        $this->assertTrue(true);
    }
}
