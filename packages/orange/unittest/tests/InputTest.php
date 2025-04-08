<?php

declare(strict_types=1);

use orange\framework\Input;
use orange\framework\exceptions\InvalidValue;

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
            'https' => 'on',
            'http_accept_language' => 'en-US,en;q=0.9',
            'http_accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        ],
        'get' => [
            'name' => 'Johnny Appleseed',
            'age' => 26,
        ],
        'cookie' => [
            'name' => 'Johnny Appleseed',
            'age' => 28,
        ],
        'valid input keys' => ['post', 'get', 'files', 'cookie', 'request', 'server', 'body'],
        'replaceable input keys' => ['post', 'get', 'files', 'cookie', 'request', 'server', 'body', 'php_sapi', 'stdin'],

        // looks like a apache server request
        'php_sapi' => 'APACHE',
        'stdin' => false,
    ];

    protected function setUp(): void
    {
        $this->instance = Input::getInstance($this->default);
    }

    // Tests
    public function testRawGet(): void
    {
        $this->assertEquals('', $this->instance->rawGet());
    }

    public function testRawBody(): void
    {
        $this->assertEquals('name=Johnny%20Appleseed&age=25', $this->instance->rawBody());
    }

    public function testHas(): void
    {
        $this->assertTrue($this->instance->has('post'));
        $this->assertFalse($this->instance->has('foo'));
    }

    public function testRequestUri(): void
    {
        $this->assertEquals('/product/123abc', $this->instance->requestUri());
    }

    public function testUriSegment(): void
    {
        $this->assertEquals('product', $this->instance->uriSegment(1));
        $this->assertEquals('123abc', $this->instance->uriSegment(2));
        $this->assertEquals('', $this->instance->uriSegment(3));
        $this->assertEquals('', $this->instance->uriSegment(0));
        $this->assertEquals('', $this->instance->uriSegment(-1));
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
            'http_accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'https' => 'on',
            'http_accept_language' => 'en-US,en;q=0.9',
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
                'http_accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'https' => 'on',
                'http_accept_language' => 'en-US,en;q=0.9',
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

    public function testReplace2(): void
    {
        $replace = [
            'GET' => [],
            'body' => '',
            'files' => [],
            'server' => [],
            'catdog' => [],
            'barfoo' => [],
            'foobar' => [],
        ];

        $this->expectException(InvalidValue::class);
        $this->assertNull($this->instance->replace($replace));
    }
}
