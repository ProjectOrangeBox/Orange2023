<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
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
    public function testAddPath(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__get(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__set(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__unset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__isset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGet(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testSet(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testUnset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testIsset(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

}
