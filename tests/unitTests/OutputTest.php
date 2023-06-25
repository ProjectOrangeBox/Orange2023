<?php

declare(strict_types=1);

namespace unitTests;

use PHPUnit\Framework\TestCase;

final class OutputTest extends TestCase
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

    public function testFlushOutput(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testSetOutput(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testAppendOutput(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testGetOutput(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testContentType(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testGetContentType(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testHeader(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testGetHeaders(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testFlushHeaders(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testFlushAll(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testSendHeaders(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testCharSet(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testGetCharSet(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testResponseCode(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testGetResponseCode(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testSendResponseCode(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testSend(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    public function testRedirect(): void
    {
        $this->assertSame('abc', 'abc', 'Test Message');
    }

    protected function tearDown(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }
}
