<?php

declare(strict_types=1);

use peels\asset\Priority;

final class PriorityTest extends \unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Priority();
    }

    // Tests
    public function testHas(): void
    {
        $this->instance->add('foobar', 'the value');

        $this->assertTrue($this->instance->has('foobar'));
    }

    public function testGet(): void
    {
        $this->instance->add('foobar', 'the value');

        $this->assertEquals('the value', $this->instance->get('foobar'));
    }

    public function testAdd(): void
    {
        $this->instance->add('foobar', 'the value');

        $this->assertEquals('the value', $this->instance->get('foobar'));

        $this->instance->add('foobar', ' testing');

        $this->assertEquals('the value testing', $this->instance->get('foobar'));

        $this->instance->add('foobar', 'testing', false);

        $this->assertEquals('testing', $this->instance->get('foobar'));
    }

    public function testAddOrder(): void
    {
        $this->instance->add('letters', 'C', Priority::NORMAL);
        $this->instance->add('letters', 'E', Priority::LATEST);
        $this->instance->add('letters', 'A', Priority::EARLIEST);
        $this->instance->add('letters', 'D', Priority::LATE);
        $this->instance->add('letters', 'B', Priority::EARLY);

        $this->assertEquals('ABCDE', $this->instance->get('letters'));
    }

    public function testAddMultiple(): void
    {
        $this->instance->addMultiple([
            'foobar' => 'the value',
            'barfoo' => 'another value',
        ]);

        $this->assertEquals('the value', $this->instance->get('foobar'));
        $this->assertEquals('another value', $this->instance->get('barfoo'));
    }
}
