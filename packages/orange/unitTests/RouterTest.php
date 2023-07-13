<?php

declare(strict_types=1);

use dmyers\orange\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private $instance;
    private $callback = ['\app\controllers\controller', 'index'];

    protected function setUp(): void
    {
        $routes = [
            'site' => 'www.example.com',
            'isHttps' => false,
            'routes' => [
                ['method' => '*', 'name' => 'product', 'url' => '/product/([a-z]+)/(\d+)'],

                ['method' => 'get', 'name' => 'productg', 'url' => '/getter/([a-z]+)/(\d+)', 'callback' => $this->callback],
                ['method' => 'post', 'name' => 'productp', 'url' => '/poster', 'callback' => $this->callback],
                ['method' => 'put', 'url' => '/putter/([a-z]+)/(\d+)', 'callback' => $this->callback],
                ['method' => 'delete', 'name' => 'productd', 'url' => '/remove/([a-z]+)', 'callback' => $this->callback],
                ['method' => 'header', 'name' => 'producth', 'url' => '/view/([a-z]+)', 'callback' => $this->callback],
            ],
        ];

        $this->instance = new Router($routes);
    }

    // Tests
    public function testMatch1(): void
    {
        $this->instance->match('/product/abc/123', 'get');

        $this->assertEquals('GET', $this->instance->getMatched('request Method'));
        $this->assertEquals('/product/abc/123', $this->instance->getMatched('request URI'));
        $this->assertEquals('/product/([a-z]+)/(\d+)', $this->instance->getMatched('matched URI'));
        $this->assertEquals('product', $this->instance->getMatched('name'));
        $this->assertEquals(null, $this->instance->getMatched('callback'));
    }

    public function testMatch2(): void
    {
        $this->instance->match('/getter/abc/123', 'get');

        $this->assertEquals('GET', $this->instance->getMatched('request Method'));
        $this->assertEquals('/getter/abc/123', $this->instance->getMatched('request URI'));
        $this->assertEquals('/getter/([a-z]+)/(\d+)', $this->instance->getMatched('matched URI'));
        $this->assertEquals('productg', $this->instance->getMatched('name'));
        $this->assertEquals($this->callback, $this->instance->getMatched('callback'));
    }

    public function testMatch3(): void
    {
        $this->instance->match('/poster', 'POST');

        $this->assertEquals('POST', $this->instance->getMatched('request Method'));
        $this->assertEquals('/poster', $this->instance->getMatched('request URI'));
        $this->assertEquals('/poster', $this->instance->getMatched('matched URI'));
        $this->assertEquals('productp', $this->instance->getMatched('name'));
        $this->assertEquals($this->callback, $this->instance->getMatched('callback'));
    }

    public function testMatch4(): void
    {
        $this->instance->match('/putter/abc/123', 'pUt');

        $this->assertEquals('PUT', $this->instance->getMatched('request Method'));
        $this->assertEquals('/putter/abc/123', $this->instance->getMatched('request URI'));
        $this->assertEquals('/putter/([a-z]+)/(\d+)', $this->instance->getMatched('matched URI'));
        $this->assertEquals(null, $this->instance->getMatched('name'));
        $this->assertEquals($this->callback, $this->instance->getMatched('callback'));
    }

    public function testMatch5(): void
    {
        $this->instance->match('/remove/rty', 'delete');

        $this->assertEquals('DELETE', $this->instance->getMatched('request Method'));
        $this->assertEquals('/remove/rty', $this->instance->getMatched('request URI'));
        $this->assertEquals('/remove/([a-z]+)', $this->instance->getMatched('matched URI'));
        $this->assertEquals('productd', $this->instance->getMatched('name'));
        $this->assertEquals($this->callback, $this->instance->getMatched('callback'));
    }

    public function testGetUrl(): void
    {
        $this->assertEquals('/product/xyz/890', $this->instance->getUrl('product', ['xyz', 890], false));
        $this->assertEquals('/poster', $this->instance->getUrl('productp', [], false));
        $this->assertEquals('/view/xyz', $this->instance->getUrl('producth', ['xyz'], false));
        $this->assertEquals('http://www.example.com/view/xyz', $this->instance->getUrl('producth', ['xyz'], true));
    }

    public function testSiteUrl(): void
    {
        $this->assertEquals('http://www.example.com', $this->instance->siteUrl(true));
        $this->assertEquals('www.example.com', $this->instance->siteUrl(false));
        $this->assertEquals('ftps://www.example.com', $this->instance->siteUrl('ftps://'));
    }
}
