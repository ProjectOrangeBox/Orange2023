<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ViewerTest extends TestCase
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
    public function testRender(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testRenderString(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddPath(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddPaths(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddPlugin(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testAddPlugins(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

}
