<?php

declare(strict_types=1);

use dmyers\orange\Container;
use dmyers\orange\Dispatcher;
use dmyers\orange\exceptions\ControllerClassNotFound;
use dmyers\orange\exceptions\MethodNotFound;

final class DispatcherTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Dispatcher::getInstance(new Container());

        include_once __DIR__ . '/support/mockController.php';
        include_once __DIR__ . '/support/mockRouter.php';
    }

    // Tests
    public function testCall(): void
    {
        $this->assertEquals('<h1>foobar</h1>', $this->instance->call([
            'argv' => [],
            'callback' => ['mockController', 'index'],
        ]));
    }

    public function testControllerClassNotFoundException(): void
    {
        $this->expectException(ControllerClassNotFound::class);
        $this->expectExceptionMessage('foobar');

        $this->instance->call([
            'argv' => [],
            'callback' => ['foobar', 'index'],
        ]);
    }

    public function testMethodNotFoundException(): void
    {
        $this->expectException(MethodNotFound::class);
        $this->expectExceptionMessage('foobar');

        $this->instance->call([
            'argv' => [],
            'callback' => ['mockController', 'foobar'],
        ]);
    }
}
