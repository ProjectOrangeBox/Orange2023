<?php

declare(strict_types=1);

use dmyers\orange\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = Input::getInstance([
            'server'=>[
                'request_uri'=>'/product/123abc',
                'request_method'=>'get',
                'http_x_requested_with'=>'xmlhttprequest',
                'http_accept'=>'application/json',
                'https'=>true,
            ],
            'post'=>[
                'name'=>'Johnny Appleseed',
                'age'=>25,
            ],
            'get'=>[
                'name'=>'Johnny Appleseed',
                'age'=>26,
            ],
            'request'=>[
                'name'=>'Johnny Appleseed',
                'age'=>27,
            ],
            'cookie'=>[
                'name'=>'Johnny Appleseed',
                'age'=>28,
            ],
        ]);
    }

    protected function tearDown(): void
    {
    }

    // Tests
    public function testRequestUri(): void
    {
    }

    public function testUriSegement(): void
    {
        $this->assertTrue(true);
    }

    public function testRequestMethod(): void
    {
        $this->assertTrue(true);
    }

    public function testRequestType(): void
    {
        $this->assertTrue(true);
    }

    public function testIsAjaxRequest(): void
    {
        $this->assertTrue(true);
    }

    public function testIsCliRequest(): void
    {
        $this->assertTrue(true);
    }

    public function testIsHttpsRequest(): void
    {
        $this->assertTrue(true);
    }

    public function testRaw(): void
    {
        $this->assertTrue(true);
    }

    public function testPost(): void
    {
        $this->assertTrue(true);
    }

    public function testGet(): void
    {
        $this->assertTrue(true);
    }

    public function testRequest(): void
    {
        $this->assertTrue(true);
    }

    public function testServer(): void
    {
        $this->assertTrue(true);
    }

    public function testFile(): void
    {
        $this->assertTrue(true);
    }

    public function testCopy(): void
    {
        $this->assertTrue(true);
    }

    public function testReplace(): void
    {
        $this->assertTrue(true);
    }
}
