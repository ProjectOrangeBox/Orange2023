<?php

declare(strict_types=1);

use orange\framework\Container;
use orange\framework\Dispatcher;
use orange\framework\exceptions\ControllerClassNotFound;
use orange\framework\exceptions\MethodNotFound;

final class DispatcherTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Dispatcher::getInstance(Container::getInstance());

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
