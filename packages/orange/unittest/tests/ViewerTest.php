<?php

declare(strict_types=1);

use orange\framework\Data;
use orange\framework\View;
use orange\framework\Input;
use orange\framework\Router;
use orange\framework\exceptions\view\ViewNotFound;

final class ViewerTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $config = [
            'view paths' => [
                WORKINGDIR . '/views',
            ],
            'view aliases' => [],
            'temp directory' => sys_get_temp_dir(),
            'debug' => false,
            'match' => '*.php',
        ];

        $this->instance = View::getInstance(
            $config,
            Data::getInstance([]),
            Router::getInstance(['site' => 'www.example.com'], Input::getInstance([
                'force https' => false,
            ])),
        );
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
        $this->assertTrue(in_array(WORKINGDIR . '/views', $this->instance->search->listDirectories()));
    }

    public function testAddPaths(): void
    {
        $this->instance->search->addDirectories([
            WORKINGDIR . '/directorySearch/foo',
            WORKINGDIR . '/directorySearch/bar'
        ]);

        $this->assertTrue(in_array(WORKINGDIR . '/directorySearch/foo', $this->instance->search->listDirectories()));
        $this->assertTrue(in_array(WORKINGDIR . '/directorySearch/bar', $this->instance->search->listDirectories()));
    }

    public function testRenderViewNotFoundException(): void
    {
        $this->expectException(ViewNotFound::class);

        $this->assertNull($this->instance->render('dummy'));
    }

    public function testChangeOption(): void
    {
        $this->instance->changeOption('debug', false);
        $this->assertFalse($this->getPrivatePublic('debug'));

        $this->instance->changeOption('debug', true);
        $this->assertTrue($this->getPrivatePublic('debug'));
    }

    public function testRecursive(): void
    {
        $this->instance->search->flushDirectories(true)->addDirectory(WORKINGDIR . '/views/errors');
        $this->assertEquals(WORKINGDIR . '/views/errors/cli/404.php', $this->instance->search->findFirst('cli/404'));
    }
}
