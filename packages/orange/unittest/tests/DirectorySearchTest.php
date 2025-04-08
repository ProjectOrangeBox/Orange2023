<?php

declare(strict_types=1);

use orange\framework\helpers\DirectorySearch;
use orange\framework\interfaces\DirectorySearchInterface;

final class DirectorySearchTest extends unitTestHelper
{
    protected $instance = null;

    protected $d1 = WORKINGDIR . '/directorySearch';
    protected $d2 = WORKINGDIR . '/directorySearch/bar';
    protected $d3 = WORKINGDIR . '/directorySearch/bbb';
    protected $d4 = WORKINGDIR . '/directorySearch/ccc';
    protected $d5 = WORKINGDIR . '/directorySearch/foo';
    protected $d6 = WORKINGDIR . '/directorySearch/aaa';

    protected $r1 = WORKINGDIR . '/directorySearch/bar/bar.php';
    protected $r2 = WORKINGDIR . '/directorySearch/bar/foo.php';
    protected $r3 = WORKINGDIR . '/directorySearch/bar/aaa/bar.php';
    protected $r4 = WORKINGDIR . '/directorySearch/bar/aaa/foo.php';
    protected $r5 = WORKINGDIR . '/directorySearch/bar/bbb/bar.php';
    protected $r6 = WORKINGDIR . '/directorySearch/bar/bbb/foo.php';

    protected function setUp(): void
    {
        if (!isset($this->instance)) {
            $this->instance = new DirectorySearch([
                'match' => '*.php',
                'quiet' => true,
                'lock after scan' => false,
                'recursive' => true,
                'normalize keys' => true,
                'locked' => false,
                'pend' => DirectorySearchInterface::PREPEND,
                'callback' => [],
            ]);
        }
    }

    public function testAddDirectory(): void
    {
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d1));
        $this->assertEquals([$this->d1], $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d2));
        $this->assertEquals([$this->d2, $this->d1], $this->instance->listDirectories());

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory($this->d3, DirectorySearchInterface::APPEND));
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
        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectory(WORKINGDIR . '/directorySearch/config'));

        $matches = [
            'app' => [0 => WORKINGDIR . '/directorySearch/config/app.php'],
            'dev/app' => [0 => WORKINGDIR . '/directorySearch/config/dev/app.php'],
        ];

        $this->assertEquals($matches, $this->instance->findAll());
    }

    public function testFindFirst(): void
    {
        $a = WORKINGDIR . '';
        $b = WORKINGDIR . '/env';

        $this->instance = new DirectorySearch([
            'quiet' => true,
            'lock after scan' => false,
            'recursive' => true,
            'extract resource key' => function ($fileInfo) {
                return $fileInfo['filename'];
            }
        ]);

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories([$a, $b]));
        $this->assertEquals(WORKINGDIR . '/configExample2.php', $this->instance->findFirst('configExample2'));
    }

    public function testFindLast(): void
    {
        $a = WORKINGDIR . '';
        $b = WORKINGDIR . '/env';

        $this->instance = new DirectorySearch([
            'quiet' => true,
            'lock after scan' => false,
            'recursive' => true,
            'extract resource key' => function ($fileInfo) {
                return $fileInfo['filename'];
            }
        ]);

        $this->assertInstanceOf(DirectorySearchInterface::class, $this->instance->addDirectories([$a, $b]));

        $this->assertEquals(WORKINGDIR . '/env/configExample2.php', $this->instance->findLast('configExample2'));
    }
}
