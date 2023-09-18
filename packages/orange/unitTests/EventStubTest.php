<?php

declare(strict_types=1);

use dmyers\orange\stubs\Event;
use dmyers\orange\interfaces\EventInterface;

final class EventStubTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Event([]);
    }

    /* Public Method Tests */

    public function testRegister(): void
    {
        $this->assertTrue(is_int($this->instance->register('open.file', function (&$payload) {
            $payload[] = 'open.file normal 1';
        })));

        $this->assertTrue(is_int($this->instance->register('close.file', ['class', 'method'])));
    }

    public function testRegisterMultiple(): void
    {
        $this->assertEquals([], $this->instance->registerMultiple([
            'open.file' => function (&$payload) {
                $payload[] = 'open.file normal 2';
            },
            'close.file' => function ($payload) {
                $payload[] = 'open.file normal 3';
            }
        ]));
    }

    public function testTrigger(): void
    {
        $this->assertInstanceOf(EventInterface::class, $this->instance->trigger('foobar'));
    }

    public function testTriggers(): void
    {
        $this->assertEquals([], $this->instance->triggers());
    }

    public function testHas(): void
    {
        $this->assertTrue($this->instance->has('foo.bar'));
    }

    public function testEvents(): void
    {
        $this->assertEquals([], $this->instance->events());
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->instance->count('foo.bar'));
    }

    public function testUnregister(): void
    {
        $this->assertTrue($this->instance->unregister(876543));
    }

    public function testUnregisterAll(): void
    {
        $this->assertTrue($this->instance->unregisterAll());
    }

}
