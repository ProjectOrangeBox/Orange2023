<?php

declare(strict_types=1);

use dmyers\orange\Router;
use dmyers\orange\Container;
use dmyers\orange\Dispatcher;
use dmyers\orange\stubs\Output;
use PHPUnit\Framework\TestCase;
use dmyers\orange\exceptions\ControllerClassNotFound;

final class DispatcherTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $output = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show header error' => false,
        ]);
        $container = Container::getInstance();

        $this->instance = Dispatcher::getInstance($output, $container);
    }

    protected function tearDown(): void
    {
    }

    // Tests
    public function testCall(): void
    {
        $router = new Router([
            'site' => 'www.example.com',
            'isHttps' => true,
            'routes' => [
                ['method' => 'get', 'url' => '/bar/foo', 'callback' => [\bogus\MainController::class, 'index'], 'name' => 'home'],
            ],
        ]);

        $router->match('/bar/foo', 'GET');

        $this->expectException(ControllerClassNotFound::class);

        $this->instance->call($router);
    }
}
