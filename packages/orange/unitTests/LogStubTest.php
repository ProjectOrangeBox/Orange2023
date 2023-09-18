<?php

declare(strict_types=1);

use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\stubs\Log;

final class LogStubTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Log([]);
    }

    /* Public Method Tests */

    public function testChangeThreshold(): void
    {
        $this->assertInstanceOf(LogInterface::class, $this->instance->changeThreshold(1));
    }

    public function testGetThreshold(): void
    {
        $this->assertEquals(0, $this->instance->getThreshold());
    }

    public function testIsEnabled(): void
    {
        $this->assertFalse($this->instance->isEnabled());
    }
}
