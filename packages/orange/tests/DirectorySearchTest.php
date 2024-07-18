<?php

declare(strict_types=1);

use orange\framework\DirectorySearch;
use orange\framework\interfaces\DirectorySearchInterface;

final class DirectorySearchTest extends unitTestHelper
{
    protected $instance = null;

    protected $d1 = __DIR__ . '/support/directorySearch';
    protected $d2 = __DIR__ . '/support/directorySearch/bar';
    protected $d3 = __DIR__ . '/support/directorySearch/bbb';
    protected $d4 = __DIR__ . '/support/directorySearch/ccc';
    protected $d5 = __DIR__ . '/support/directorySearch/foo';
    protected $d6 = __DIR__ . '/support/directorySearch/aaa';

    protected $r1 = __DIR__ . '/support/directorySearch/bar/bar.php';
    protected $r2 = __DIR__ . '/support/directorySearch/bar/foo.php';
    protected $r3 = __DIR__ . '/support/directorySearch/bar/aaa/bar.php';
    protected $r4 = __DIR__ . '/support/directorySearch/bar/aaa/foo.php';
    protected $r5 = __DIR__ . '/support/directorySearch/bar/bbb/bar.php';
    protected $r6 = __DIR__ . '/support/directorySearch/bar/bbb/foo.php';

    protected function setUp(): void
    {
        if (!isset($this->instance)) {
            $this->instance = new DirectorySearch([
                'match' => '*.php',
                'quiet' => true,
                'lock after scan' => false,
                'recursive' => true,
            ]);
        }
    }

    public function testAddDirectory(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d1));
        $this->assertEquals([$this->d1], $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d2));
        $this->assertEquals([$this->d2, $this->d1], $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d3, false));
        $this->assertEquals([$this->d2, $this->d1, $this->d3], $this->instance->listDirectories());
    }

    public function testAddDirectories(): void
    {
        $directories = [$this->d1, $this->d2];

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories($directories));
        $this->assertEquals($directories, $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d3));
        $this->assertEquals([$this->d3, $this->d1, $this->d2], $this->instance->listDirectories());
    }

    public function testRemoveDirectory(): void
    {
        $directories = [$this->d2, $this->d3, $this->d4];

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories($directories));
        $this->assertEquals($directories, $this->instance->listDirectories());
        $this->assertTrue($this->instance->directoryExists($this->d3));

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->removeDirectory($this->d2));
        $this->assertEquals([$this->d3, $this->d4], $this->instance->listDirectories());
    }

    public function testRemoveDirectories(): void
    {
        $directories = [$this->d2, $this->d3, $this->d4];

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories($directories));
        $this->assertEquals($directories, $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->removeDirectories([$this->d3, $this->d4]));
        $this->assertEquals([$this->d2], $this->instance->listDirectories());
    }

    public function testReplaceDirectories(): void
    {
        $directories1 = [$this->d1, $this->d2];
        $directories2 = [$this->d3, $this->d4];

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories($directories1));
        $this->assertEquals($directories1, $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->replaceDirectories($directories2));
        $this->assertEquals($directories2, $this->instance->listDirectories());
    }

    /* resources */

    public function testAddResource(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addResource('bar', $this->r1));
        $this->assertTrue($this->instance->exists('bar'));
        $this->assertFalse($this->instance->exists('foo'));
    }

    public function testAddResources(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addResources(['nope.html' => '/bogus/nope.html', 'bar.html' => $this->r1, 'foo.html' => $this->r2]));
        $this->assertTrue($this->instance->exists('bar.html'));
        $this->assertTrue($this->instance->exists('foo.html'));
        $this->assertFalse($this->instance->exists('nope.html'));
    }

    public function testReplaceResources(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addResources(['nope.html' => '/bogus/nope.html', 'bar.html' => $this->r1, 'foo.html' => $this->r2]));
        $this->assertTrue($this->instance->exists('bar.html'));
        $this->assertTrue($this->instance->exists('foo.html'));
        $this->assertFalse($this->instance->exists('nope.html'));

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->replaceResources(['nope' => '/bogus/nope.html', 'aaa' => $this->r1, 'bbb' => $this->r2]));
        $this->assertTrue($this->instance->exists('aaa'));
        $this->assertTrue($this->instance->exists('bbb'));
        $this->assertFalse($this->instance->exists('nope'));
    }

    public function testFindAll(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory(__DIR__ . '/support/directorySearch/config'));

        $matches = [
            'app' => [0 => __DIR__ . '/support/directorySearch/config/app.php'],
            'dev/app' => [0 => __DIR__ . '/support/directorySearch/config/dev/app.php'],
        ];

        $this->assertEquals($matches, $this->instance->findAll());
    }

    public function testFindFirst(): void
    {
        $a = __DIR__ . '/support';
        $b = __DIR__ . '/support/env';

        $this->instance = new DirectorySearch([
            'quiet' => true,
            'lock after scan' => false,
            'recursive' => true,
            'extractResourceName' => function ($fileInfo) {
                return $fileInfo['filename'];
            }
        ]);

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories([$a, $b]));
        $this->assertEquals(__DIR__ . '/support/configExample2.php', $this->instance->findFirst('configExample2'));
    }

    public function testFindLast(): void
    {
        $a = __DIR__ . '/support';
        $b = __DIR__ . '/support/env';

        $this->instance = new DirectorySearch([
            'quiet' => true,
            'lock after scan' => false,
            'recursive' => true,
            'extractResourceName' => function ($fileInfo) {
                return $fileInfo['filename'];
            }
        ]);

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories([$a, $b]));

        $this->assertEquals(__DIR__ . '/support/env/configExample2.php', $this->instance->findLast('configExample2'));
    }
}
