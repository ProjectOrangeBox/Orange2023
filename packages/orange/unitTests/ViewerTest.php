<?php

declare(strict_types=1);

use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\exceptions\ViewNotFound;

final class ViewerTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new View([], new Data());

        $this->instance->addPath(__DIR__ . '/support/views');
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
        $this->assertTrue(in_array(__DIR__ . '/support/views', $this->getPrivatePublic('viewPaths')));
    }

    public function testAddPaths(): void
    {
        $this->instance->addPaths(['/foo', '/bar']);

        $this->assertTrue(in_array('/foo', $this->getPrivatePublic('viewPaths')));
        $this->assertTrue(in_array('/bar', $this->getPrivatePublic('viewPaths')));
    }

    public function testAddPlugin(): void
    {
        $this->instance->addPlugin('strtolower', ['function', 'strtolower']);

        $this->assertTrue(isset($this->getPrivatePublic('plugins')['strtolower']));
    }

    public function testAddPlugins(): void
    {
        $this->instance->addPlugins([
            'strtoupper' => ['function' => 'strtoupper'],
            'trimmer' => ['funciton' => 'trim'],
        ]);

        $this->assertTrue(isset($this->getPrivatePublic('plugins')['strtoupper']));
        $this->assertTrue(isset($this->getPrivatePublic('plugins')['trimmer']));
    }

    public function testRenderViewNotFoundException()
    {
        $this->expectException(ViewNotFound::class);
        $this->expectExceptionMessage('View "dummy" Extension ".php" Not Found.');

        $this->instance->render('dummy');
    }
}
