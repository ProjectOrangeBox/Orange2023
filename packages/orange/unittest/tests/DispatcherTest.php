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
        $container->set('config', Config::getInstance(['config directory' => WORKINGDIR . '/config']));
        $container->set('input', Input::getInstance(['server' => $_SERVER]));
        $container->set('output', Output::getInstance([], $container->input));

        $this->instance = Dispatcher::getInstance($container);

        include_once MOCKDIR . '/mockController.php';
    }

    // Tests
    public function testCall(): void
    {
        $this->assertEquals('<h1>foobar</h1>', $this->instance->call([
            'argv' => [],
            // global mockController.php class loaded above
            'callback' => ['mockController', 'index'],
        ]));
    }

    public function testControllerClassNotFoundException(): void
    {
        $this->expectException(ControllerClassNotFound::class);

        $this->assertNull($this->instance->call([
            'argv' => [],
            'callback' => ['foobar', 'index'],
        ]));
    }

    public function testMethodNotFoundException(): void
    {
        $this->expectException(MethodNotFound::class);

        $this->assertNull($this->instance->call([
            'argv' => [],
            // global mockController.php class loaded above has no foobar method
            'callback' => ['mockController', 'foobar'],
        ]));
    }
}
