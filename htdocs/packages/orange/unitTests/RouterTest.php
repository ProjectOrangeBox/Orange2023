<?php

declare(strict_types=1);

use dmyers\orange\Router;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\RouteNotFound;
use dmyers\orange\exceptions\ConfigNotFound;
use dmyers\orange\exceptions\RouterNameNotFound;

final class RouterTest extends unitTestHelper
{
    protected $instance;
    protected $callback = ['\app\controllers\controller', 'index'];

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

    public function testGetMatchedInvalidValueException(): void
    {
        $this->instance->match('/product/abc/123', 'get');

        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Unknown routing value "foobar"');

        $this->instance->getMatched('foobar'); // there is no value foobar
    }

    public function testGetUrlInvalidValueException(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Parameter count mismatch. Expecting 2 got 1 route named "product".');

        $this->assertEquals('/product/xyz/890', $this->instance->getUrl('product', [890], false));
    }

    public function testGetUrlParameterInvalidValueException(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Parameter mismatch. Expecting \d+ got xyz route named "product".');

        $this->assertEquals('/product/xyz/890', $this->instance->getUrl('product', ['abc', 'xyz'], false));
    }

    public function testMatchRouteNotFoundException1(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('[GET]/bla/bla/bla');

        $this->instance->match('/bla/bla/bla', 'GET');
    }

    public function testMatchRouteNotFoundException2(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('[GET]/poster');

        $this->instance->match('/poster', 'GET');
    }

    public function testGetUrlRouterNameNotFound(): void
    {
        $this->expectException(RouterNameNotFound::class);
        $this->expectExceptionMessage('url route named "notreal" not found');

        $this->instance->getUrl('notreal');
    }

    public function testConfigNotFoundException(): void
    {
        $routes = [
            'site' => null, // REQUIRED!
            'isHttps' => false,
            'routes' => [],
        ];

        $this->expectException(ConfigNotFound::class);
        $this->expectExceptionMessage('Route config "site" in routes.php can not be empty.');

        new Router($routes);
    }
}
