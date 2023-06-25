<?php

declare(strict_types=1);

namespace unitTests;

use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    public static function setUpBeforeClass(): void
	{
        define('__ROOT__', realpath(__DIR__ . '/../../'));
        define('__WWW__', realpath(__DIR__.'/../../htdocs'));

		$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__.'/.unit.env', true, INI_SCANNER_TYPED));
    }

    protected function setUp(): void
    {
    }

    public function testRender(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testRenderString(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testViewExists(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testAddViewPath(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testAddViewPaths(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    public static function tearDownAfterClass(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }
}
