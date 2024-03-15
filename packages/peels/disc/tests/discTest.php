<?php

declare(strict_types=1);

/*
In __root__

./vendor/bin/phpunit ./libraries/disc/tests

./vendor/bin/phpunit ./libraries/disc/tests/directoryTest.php

*/

use peels\disc\disc;
use PHPUnit\Framework\TestCase;
use peels\disc\exceptions\DiscException;

final class discTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('__ROOT__')) {
            define('__ROOT__', realpath(__DIR__ . '/support'));
        }

        disc::root(__ROOT__);
    }

    public function tearDown(): void
    {
        disc::directory('/working')->removeContents();
    }

    public function testGetRoot(): void
    {
        $this->assertEquals(__ROOT__, disc::getRoot());
    }

    public function testResolve(): void
    {
        $this->assertEquals(__ROOT__ . '/123.txt', disc::resolve('/123.txt'));
        $this->assertEquals(__ROOT__ . '/123.txt', disc::resolve('/123.txt', false));

        $this->expectException(DiscException::class);
        disc::resolve('/xyz.txt', true, 'file');
    }

    public function testStripRootPath(): void
    {
        $newPath = disc::stripRootPath('/file/path.txt', true);
        $this->assertEquals('/file/path.txt', $newPath);

        $newPath = disc::stripRootPath(__ROOT__ . '/file/path.txt', true);
        $this->assertEquals('/file/path.txt', $newPath);

        $newPath = disc::stripRootPath(__ROOT__ . '/file/path.txt', false);
        $this->assertEquals(__ROOT__ . '/file/path.txt', $newPath);
    }

    public function testExists(): void
    {
        $this->assertEquals(true, disc::exists('/files/123.txt'));
        $this->assertEquals(false, disc::exists('/files/xyz.txt'));


        $this->assertEquals(true, disc::exists('/files/testfolder'));
        $this->assertEquals(false, disc::exists('/files/foobar'));
    }

    public function testFile(): void
    {
        $this->assertInstanceOf(\peels\disc\disc\File::class, disc::file('/foo.txt'));
    }

    public function testDirectory(): void
    {
        $this->assertInstanceOf(\peels\disc\disc\Directory::class, disc::directory('/foobar'));
    }

    public function testFormatSize(): void
    {
        $this->assertEquals('1kB', disc::formatSize(1024));
        $this->assertEquals('1.9kB', disc::formatSize(1967));
        $this->assertEquals('234B', disc::formatSize(234));
        $this->assertEquals('979.5kB', disc::formatSize(1002983));
        $this->assertEquals('9.54MB', disc::formatSize(10002983));
        $this->assertEquals('95.37MB', disc::formatSize(100002983));
        $this->assertEquals('953.68MB', disc::formatSize(1000002983));
        $this->assertEquals('9.31GB', disc::formatSize(10000002983));
        $this->assertEquals('9.095TB', disc::formatSize(10000000002983));
    }

    public function testFormatTime(): void
    {
        $this->assertEquals(1, disc::formatTime(1));

        $this->assertEquals('1970-01-01 00:00:00', disc::formatTime(0, 'Y-m-d H:i:s'));
        $this->assertEquals('1990-12-25 23:58:00', disc::formatTime(662169480, 'Y-m-d H:i:s'));
    }

    public function testFormatMode(): void
    {
        $this->assertEquals('urwxrwxrwx', disc::formatMode(0777));
        $this->assertEquals('u', disc::formatMode(0777, DISC::TYPE));
        $this->assertEquals('rwxrwxrwx', disc::formatMode(0777, DISC::PERMISSION));
        $this->assertEquals('urwxrwxrwx', disc::formatMode(0777, DISC::ALL));
    }

    public function testAutoGenMissingDirectory(): void
    {
        $this->assertEquals(true, disc::autoGenMissingDirectory('/working/x/y/z/newtestfolder.txt'));

        $this->assertTrue(disc::exists('/working/x/y/z'));
        $this->assertFalse(disc::exists('/working/x/y/z/newtestfolder.txt'));

        $this->assertEquals(true, disc::autoGenMissingDirectory('/working/x/a/b/newtestfolder.txt'));
        $this->assertTrue(disc::exists('/working/x/a/b'));
    }
}
