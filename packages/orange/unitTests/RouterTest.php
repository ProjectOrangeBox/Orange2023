<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private $instance;
    
    protected function setUp(): void
    {
        $this->instance = null; #change
        
        fwrite(STDOUT, __METHOD__ . "\n");
    }
    
    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    // Tests
    public function testMatch(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGetMatched(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testGetUrl(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testSiteUrl(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

}
