<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }
    
    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    // Tests
    public function testSetServices(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGetService(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGetServiceIfExists(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__get(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGet(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__set(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testSet(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddAlias(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddFactory(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddValue(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGetServices(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__isset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testIsset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testHas(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__unset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testUnset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testRemove(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__debugInfo(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testDebugInfo(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

}
