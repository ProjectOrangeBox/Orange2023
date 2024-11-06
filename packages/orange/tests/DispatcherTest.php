<?php

declare(strict_types=1);

use orange\framework\Input;
use orange\framework\Config;
use orange\framework\Output;
use orange\framework\Container;
use orange\framework\Dispatcher;
use orange\framework\exceptions\dispatcher\MethodNotFound;
use orange\framework\exceptions\dispatcher\ControllerClassNotFound;

final class DispatcherTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $container = Container::getInstance([]);

        $container->set('config', Config::getInstance([]));
        $container->set('input', Input::getInstance(['server' => $_SERVER, 'force https' => false]));
        $container->set('output', Output::getInstance([]));

        $this->instance = Dispatcher::getInstance($container);

        include_once MOCKFOLDER . '/mockController.php';
        include_once MOCKFOLDER . '/mockRouter.php';
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
