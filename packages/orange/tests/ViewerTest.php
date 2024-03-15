<?php

declare(strict_types=1);

use orange\framework\Data;
use orange\framework\View;
use orange\framework\exceptions\ResourceNotFound;

final class ViewerTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $config = [
            'view paths' => [],
            'view aliases' => [],
            'temp folder' => sys_get_temp_dir(),
            'debug' => false,
            'extension' => '.php',
        ];

        $this->instance = View::getInstance($config, Data::getInstance([]));

        $this->instance->viewSearch->addDirectory(__DIR__ . '/support/views');
    }

    // Tests
    public function testRender(): void
    {
        $this->assertEquals('<h1>Hello World</h1>', $this->instance->render('test', ['hello' => 'Hello World']));
    }

    public function testRenderString(): void
    {
        $this->assertEquals('<h1>Hello World</h1>', $this->instance->renderString('<h1><?=$hello ?></h1>', ['hello' => 'Hello World']));
    }

    public function testAddPath(): void
    {
        // path added above let's test for it.
        $this->assertTrue(in_array(__DIR__ . '/support/views', $this->instance->viewSearch->list()));
    }

    public function testAddPaths(): void
    {
        $this->instance->viewSearch->addDirectories(['/foo', '/bar']);

        $this->assertTrue(in_array('/foo', $this->instance->viewSearch->list()));
        $this->assertTrue(in_array('/bar', $this->instance->viewSearch->list()));
    }

    public function testRenderViewNotFoundException(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->instance->render('dummy');
    }

    public function testChangeOption(): void
    {
        $this->instance->change('debug', false);
        $this->assertFalse($this->getPrivatePublic('debug'));

        $this->instance->change('debug', true);
        $this->assertTrue($this->getPrivatePublic('debug'));
    }
}
