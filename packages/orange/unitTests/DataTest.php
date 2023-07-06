<?php

declare(strict_types=1);

use dmyers\orange\Data;
use PHPUnit\Framework\TestCase;

final class DataTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new Data();
    }

    protected function tearDown(): void
    {
    }

    public function testData(): void
    {
        $this->instance['name'] = 'Johnny Appleseed';

        $this->assertEquals('Johnny Appleseed', $this->instance['name']);
        $this->assertEquals(1, count($this->instance));
    }

    // Tests
    public function testMerge(): void
    {
        $this->instance->merge(['age'=>21,'food'=>'pizza']);

        $this->assertEquals((array)$this->instance,['name'=>'Johnny Appleseed','age'=>21,'food'=>'pizza']);
        $this->assertEquals(3, count($this->instance));

        $this->instance->merge(['name'=>'Jenny Appleseed','age'=>25]);

        $this->assertEquals((array)$this->instance,['name'=>'Jenny Appleseed','age'=>25,'food'=>'pizza']);
        $this->assertEquals(3, count($this->instance));
    }
}
