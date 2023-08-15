<?php

declare(strict_types=1);

use dmyers\orange\Input;

final class InputTest extends unitTestHelper
{
    protected $instance;

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

            'convert keys to' => 'lowercase',
            're key filter' => '@[^a-z0-9 \[\]\-_]+@',
            'valid input keys' => ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'],

            // looks like a apache server request
            'PHP_SAPI' => 'APACHE',
            'STDIN' => false,
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
        $replace = [
            'raw' => [],
            'file' => [],
            'server' => [],
            'post' => [
                'name' => 'Jenny Appleseed',
                'age' => 29,
            ],
            'get' => [],
            'request' => [],
            'cookie' => [],
        ];

        $this->instance->replace($replace);

        $this->assertEquals($replace, $this->instance->copy());
    }

    public function testKeysFilter(): void
    {
        $this->instance = new Input([
            'post' => [
                'name[]' => 'Johnny Appleseed',
                'NameHere' => 1,
                'name_here' => 2,
                'name-here' => 3,
                'NameHere' => 4,
                'NAME123[]' => 5,
                'NAME HERE' => 6,
                'NAME#HERE' => 7,
                'NAME#$%^&*()HERE' => 8,
                'NA#$%^&*ME#HERE' => 9,
                'NAME%20HERE' => 10,
            ],
            'convert keys to' => 'lowercase',
            're key filter' => '@[^a-z0-9 \[\]\-_]+@',
            'valid input keys' => ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'],
        ]);

        $debug = $this->instance->__debugInfo();

        $this->assertEquals([
            'name[]' => 'Johnny Appleseed',
            'namehere' => 9,
            'name_here' => 2,
            'name-here' => 3,
            'name123[]' => 5,
            'name here' => 6,
            'name20here' => 10,
        ], $this->getPrivatePublic('input')['post']);
    }
}
