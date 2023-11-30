<?php

declare(strict_types=1);

use dmyers\orange\Input;

final class InputTest extends unitTestHelper
{
    protected $instance;
    private $default = [
        'body' => 'name=Johnny%20Appleseed&age=25',
        'files' => [],
        'server' => [
            'request_uri' => '/product/123abc',
            'request_method' => 'get',
            'http_x_requested_with' => 'xmlhttprequest',
            'http_accept' => 'application/json',
            'https' => 'on',
        ],
        'get' => [
            'name' => 'Johnny Appleseed',
            'age' => 26,
        ],
        'cookie' => [
            'name' => 'Johnny Appleseed',
            'age' => 28,
        ],
        'valid input keys' => ['body',  'get', 'server', 'files', 'cookie'],
        
        // looks like a apache server request
        'PHP_SAPI' => 'APACHE',
        'STDIN' => false,
    ];

    protected function setUp(): void
    {
        $this->instance = new Input($this->default);
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
        $this->assertEquals('', $this->instance->uriSegement(3));
        $this->assertEquals('', $this->instance->uriSegement(0));
        $this->assertEquals('', $this->instance->uriSegement(-1));
    }

    public function testRequestMethod(): void
    {
        $this->assertEquals('get', $this->instance->requestMethod());
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

    public function testBody(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 25,
        ], $this->instance->body());
    }

    public function testPost(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 25,
        ], $this->instance->body());
    }

    public function testGet(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 26,
        ], $this->instance->get());
    }

    public function testServer(): void
    {
        $this->assertEquals([
            'request_uri' => '/product/123abc',
            'request_method' => 'get',
            'http_x_requested_with' => 'xmlhttprequest',
            'http_accept' => 'application/json',
            'https' => 'on',

        ], $this->instance->server());
    }

    public function testFiles(): void
    {
        $this->assertEquals([], $this->instance->files());
    }

    public function testCookie(): void
    {
        $this->assertEquals([
            'name' => 'Johnny Appleseed',
            'age' => 28,
        ], $this->instance->Cookie());
    }

    public function testCopy(): void
    {
        $this->assertEquals([
            'body' => 'name=Johnny%20Appleseed&age=25',
            'files' => [],
            'server' => [
                'request_uri' => '/product/123abc',
                'request_method' => 'get',
                'http_x_requested_with' => 'xmlhttprequest',
                'http_accept' => 'application/json',
                'https' => 'on',
            ],
            'get' => [
                'name' => 'Johnny Appleseed',
                'age' => 26,
            ],
            'cookie' => [
                'name' => 'Johnny Appleseed',
                'age' => 28,
            ],
            'post' => [],
            'request' => [],
        ], $this->instance->copy());
    }

    public function testReplace(): void
    {
        $replace = [
            'get' => [],
            'body' => '',
            'files' => [],
            'server' => [],
            'cookie' => [],
            'post' => [],
            'request' => [],
        ];

        $this->instance->replace($replace);

        $this->assertEquals($replace, $this->instance->copy());
    }
}
