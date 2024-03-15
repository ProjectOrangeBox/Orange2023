<?php

declare(strict_types=1);

use peels\disc\Disc;
use PHPUnit\Framework\TestCase;
use peels\disc\exceptions\DiscException;

final class directoryTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('__ROOT__')) {
            define('__ROOT__', realpath(__DIR__ . '/support'));
        }

        Disc::root(__ROOT__);
    }

    public function tearDown(): void
    {
        Disc::directory('/working')->removeContents();
    }

    public function testList(): void
    {
        $this->assertEquals(['/files/123.txt', '/files/newfile.txt'], disc::directory('/files')->list('*.txt'));
    }

    public function testListAll(): void
    {
        $this->assertEquals(['/files/123.txt', '/files/newfile.txt', '/files/testfolder/test_file.txt'], disc::directory('/files')->listAll('*.txt'));
    }

    public function testCopyRemoveContents(): void
    {
            /* copy part */;
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir = disc::directory('/files')->copy('/working/foobar'));
        $this->assertTrue($newDir->exists());
        $this->assertTrue($newDir->exists('/123.txt'));
        $this->assertTrue($newDir->exists('/testfolder/test_file.txt'));

        /* remove entire folder - including folder */
        $this->assertTrue($newDir->remove());
        $this->assertFalse($newDir->exists());

        /* copy part */
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir = disc::directory('/files')->copy('/working/foobar'));
        $this->assertTrue($newDir->exists());

        /* remove folders contents */
        $this->assertTrue($newDir->removeContents());
        $this->assertTrue($newDir->exists());

        $this->assertTrue($newDir->remove());
        $this->assertFalse($newDir->exists());
    }

    public function testRename(): void
    {
        /* copy part */
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir = disc::directory('/files')->copy('/working/foo'));
        $this->assertTrue($newDir->exists());

        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir->rename('bar'));

        $this->assertEquals('bar', $newDir->name('bar'));
        $this->assertEquals(__ROOT__ . '/working/bar', $newDir->getPath());

        $this->expectException(DiscException::class);
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir->rename('/foo/bar'));
    }

    public function testMove(): void
    {
        /* copy part */
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir = disc::directory('/files')->copy('/working/foobar'));
        $this->assertTrue($newDir->exists());

        $this->assertInstanceOf(\peels\disc\disc\Directory::class, $newDir->move('/working/new/folder/here'));
        $this->assertTrue($newDir->exists());
        $this->assertEquals('here', $newDir->name('here'));
        $this->assertEquals(__ROOT__ . '/working/new/folder/here', $newDir->getPath());
    }

    public function testType(): void
    {
        $this->assertEquals('dir', disc::directory('/files')->type());
    }

    public function testTouch(): void
    {
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, disc::directory('/files'));
    }

    public function testInfo(): void
    {
        $this->assertIsArray(disc::directory('/files')->info());
    }

    public function testIsDirectory(): void
    {
        $this->assertFalse(disc::directory('/foo.txt')->isDir());

        $this->expectException(DiscException::class);
        $this->assertFalse(disc::directory('/files/123.txt')->isDir());

        $this->expectException(DiscException::class);
        $this->assertTrue(disc::directory('/files')->isDir());
    }

    public function testDirectory(): void
    {
        $this->assertEquals('/', disc::directory('/files')->directory());
        $this->assertEquals('/files', disc::directory('/files/testfolder')->directory());
    }

    public function testSize(): void
    {
        $this->assertEquals(224, disc::directory('/files')->size());
    }

    public function testAccessTime(): void
    {
        $this->assertEquals(fileatime(__ROOT__ . '/files'), disc::directory('/files')->accessTime());
        $this->assertEquals(date('Y-m-d H:i:s', fileatime(__ROOT__ . '/files')), disc::directory('/files')->accessTime('Y-m-d H:i:s'));
    }

    public function testChangeTime(): void
    {
        $this->assertEquals(filectime(__ROOT__ . '/files'), disc::directory('/files')->changeTime());
        $this->assertEquals(date('Y-m-d H:i:s', filectime(__ROOT__ . '/files')), disc::directory('/files')->changeTime('Y-m-d H:i:s'));
    }

    public function testModificationTime(): void
    {
        $this->assertEquals(filemtime(__ROOT__ . '/files'), disc::directory('/files')->modificationTime());
        $this->assertEquals(date('Y-m-d H:i:s', filemtime(__ROOT__ . '/files')), disc::directory('/files')->modificationTime('Y-m-d H:i:s'));
    }

    public function testGroup(): void
    {
        $this->assertEquals(filegroup(__ROOT__ . '/files'), disc::directory('/files')->group());
        $this->assertEquals(posix_getgrgid(filegroup(__ROOT__ . '/files'))['name'], disc::directory('/files')->groupName());
    }

    public function testOwner(): void
    {
        $this->assertEquals(fileowner(__ROOT__ . '/files'), disc::directory('/files')->owner());
        $this->assertEquals(posix_getpwuid(fileowner(__ROOT__ . '/files'))['name'], disc::directory('/files')->ownerName());
    }

    public function testPermissions(): void
    {
        disc::directory('/files')->changePermissions(0777);

        $this->assertEquals(0777, disc::directory('/files')->permissions());
        $this->assertEquals('d', disc::directory('/files')->permissions(1));
        $this->assertEquals('rwxrwxrwx', disc::directory('/files')->permissions(2));
        $this->assertEquals('drwxrwxrwx', disc::directory('/files')->permissions(3));
    }
}
