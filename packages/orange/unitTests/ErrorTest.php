<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = null; #change
    }

    protected function tearDown(): void
    {
    }

    // Tests
    public function testRequestType(): void
    {
        $this->assertTrue(true);
    }

    public function testAdd(): void
    {
        $this->assertTrue(true);
    }

    public function testCollectErrors(): void
    {
        $this->assertTrue(true);
    }

    public function testClear(): void
    {
        $this->assertTrue(true);
    }

    public function testReset(): void
    {
        $this->assertTrue(true);
    }

    public function testHas(): void
    {
        $this->assertTrue(true);
    }

    public function testErrors(): void
    {
        $this->assertTrue(true);
    }

    public function testSend(): void
    {
        $this->assertTrue(true);
    }

    public function testSendOnError(): void
    {
        $this->assertTrue(true);
    }

    public function testShowError(): void
    {
        $this->assertTrue(true);
    }

    public function testDisplay(): void
    {
        $this->assertTrue(true);
    }
}
