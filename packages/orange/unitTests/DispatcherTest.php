<?php

declare(strict_types=1);

use dmyers\orange\Container;
use dmyers\orange\Dispatcher;
use PHPUnit\Framework\TestCase;
use dmyers\orange\exceptions\ControllerClassNotFound;
use dmyers\orange\exceptions\MethodNotFound;

final class DispatcherTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = Dispatcher::getInstance(new Container());

        include_once __DIR__ . '/support/controllerClass.php';
        include_once __DIR__ . '/support/bogusRouter.php';
    }

    // Tests
    public function testCall(): void
    {
        $router = new BogusRouter([
            'argv' => [],
            'callback' => ['controllerClass', 'index'],
        ]);

        $this->assertEquals('<h1>foobar</h1>',$this->instance->call($router));
    }

    public function testControllerClassNotFoundException(): void
    {
        $this->expectException(ControllerClassNotFound::class);

        $router = new BogusRouter([
            'argv' => [],
            'callback' => ['foobar', 'index'],
        ]);

        $this->instance->call($router);
    }

    public function testMethodNotFoundException(): void
    {
        $this->expectException(MethodNotFound::class);

        $router = new BogusRouter([
            'argv' => [],
            'callback' => ['controllerClass', 'foobar'],
        ]);

        $this->instance->call($router);
    }
}
