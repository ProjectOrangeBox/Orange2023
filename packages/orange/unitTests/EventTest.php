<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
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
    public function testRegister(): void
    {        
        $this->assertTrue(true);
    }

    public function testRegisterMultiple(): void
    {        
        $this->assertTrue(true);
    }

    public function testTrigger(): void
    {        
        $this->assertTrue(true);
    }

    public function testHas(): void
    {        
        $this->assertTrue(true);
    }

    public function testTriggers(): void
    {        
        $this->assertTrue(true);
    }

    public function testUnregister(): void
    {        
        $this->assertTrue(true);
    }

    public function testUnregisterAll(): void
    {        
        $this->assertTrue(true);
    }

}
