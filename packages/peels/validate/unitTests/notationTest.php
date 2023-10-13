<?php

declare(strict_types=1);

use peels\validate\Notation;

final class notationTest extends \unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Notation('.');
    }

    public function testSet(): void
    {
        $array = [
            'age' => 23,
        ];

        $this->instance->set($array, 'name.first', 'Johnny');
        $this->instance->set($array, 'name.last', 'Appleseed');

        $this->assertEquals(23, $array['age']);
        $this->assertEquals('Johnny', $array['name']['first']);
        $this->assertEquals('Appleseed', $array['name']['last']);
    }

    public function testGet(): void
    {
        $array = [
            'age' => 23,
        ];

        $this->instance->set($array, 'name.first', 'Johnny');
        $this->instance->set($array, 'name.last', 'Appleseed');

        $this->assertEquals(23, $array['age']);
        $this->assertEquals('Johnny', $array['name']['first']);
        $this->assertEquals('Appleseed', $array['name']['last']);

        $this->assertEquals(23, $this->instance->get($array, 'age'));
        $this->assertEquals('Johnny', $this->instance->get($array, 'name.first'));
        $this->assertEquals('Appleseed', $this->instance->get($array, 'name.last'));
        $this->assertEquals('foobar', $this->instance->get($array, 'dummy.key', 'foobar'));
        $this->assertEquals(null, $this->instance->get($array, 'dummy.key2'));
    }

    public function testIsSet(): void
    {
        $array = [
            'age' => 23,
        ];

        $this->instance->set($array, 'name.first', 'Johnny');
        $this->instance->set($array, 'name.last', 'Appleseed');

        $this->assertTrue($this->instance->isset($array, 'age'));
        $this->assertTrue($this->instance->isset($array, 'name.first'));
        $this->assertTrue($this->instance->isset($array, 'name.last'));
        $this->assertFalse($this->instance->isset($array, 'name.middle'));
        $this->assertFalse($this->instance->isset($array, 'color'));
    }

    public function testUnSet(): void
    {
        $array = [
            'age' => 23,
        ];

        $match = [
            'age' => 23,
            'name' => ['last' => 'Appleseed'],
        ];

        $this->instance->set($array, 'name.first', 'Johnny');
        $this->instance->set($array, 'name.last', 'Appleseed');

        $this->instance->unset($array, 'name.first');
        $this->assertEquals($match, $array);
    }

    public function testFlatten(): void
    {
        $input = [
            'age' => 23,
            'name' => [
                'first' => 'Johnny',
                'last' => 'Appleseed',
            ],
        ];

        $match = [
            'age' => 23,
            'name.first' => 'Johnny',
            'name.last' => 'Appleseed',
        ];

        $this->assertEquals($match, $this->instance->flatten($input));
    }

    public function testExpand(): void
    {
        $input = [
            'name.first' => 'Johnny',
            'name.last' => 'Appleseed',
            'age' => 23,
        ];

        $match = [
            'age' => 23,
            'name' => [
                'first' => 'Johnny',
                'last' => 'Appleseed',
            ],
        ];

        $this->assertEquals($match, $this->instance->expand($input));
    }
}
