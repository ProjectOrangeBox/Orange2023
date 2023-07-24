<?php

declare(strict_types=1);

use dmyers\orange\Container;
use PHPUnit\Framework\TestCase;
use dmyers\orange\exceptions\ServiceNotFound;
use dmyers\orange\interfaces\ContainerInterface;

final class ContainerTest extends TestCase
{
    private $instance;
    private $services;

    protected function setUp(): void
    {
        $this->instance = new Container();

        $this->services = [
            'foo' => 'bar',
            'cookie' => function (ContainerInterface $container) {
                return new stdClass();
            },
            '@cat' => 'foo', // alias of foo
        ];
    }

    // Tests
    public function testSetServices(): void
    {
        $this->assertEquals(new Container, $this->instance->setServices($this->services));
    }

    public function testGetService(): void
    {
        $this->instance->setServices($this->services);

        $this->assertEquals('bar', Container::getService('foo'));
        $this->assertEquals('bar', Container::getService('cat'));
        $this->assertEquals(new stdClass, Container::getService('cookie'));
    }

    public function testGetServiceIfExists(): void
    {
        $this->instance->setServices($this->services);

        $this->assertNull(Container::getServiceIfExists('foobar'));
        $this->assertEquals('bar', Container::getServiceIfExists('foo'));
    }

    public function test__get(): void
    {
        $this->instance->setServices($this->services);

        $this->assertEquals('bar', $this->instance->foo);
        $this->assertEquals('bar', $this->instance->cat);
        $this->assertEquals(new stdClass, $this->instance->cookie);
    }

    public function testGet(): void
    {
        $this->instance->setServices($this->services);

        $this->assertEquals('bar', $this->instance->get('foo'));
        $this->assertEquals('bar', $this->instance->get('cat'));
        $this->assertEquals(new stdClass, $this->instance->get('cookie'));
    }

    public function test__set(): void
    {
        $this->instance->food = 'pizza';

        $this->assertEquals('pizza', $this->instance->get('food'));
    }

    public function testSet(): void
    {
        $this->instance->water = 'blue';

        $this->assertEquals('blue', $this->instance->water);
    }

    public function testAddAlias(): void
    {
        $this->instance->addAlias('sky', 'water');

        $this->assertEquals('blue', $this->instance->sky);
    }

    public function testAddClosure(): void
    {
        $this->instance->addClosure('factory', function () {
            $class = new stdClass();

            $class->name = 'Johnny Appleseed';

            return $class;
        });

        $class = new stdClass();

        $class->name = 'Johnny Appleseed';


        $this->assertEquals($class, $this->instance->factory);
    }

    public function testAddValue(): void
    {
        $this->instance->addValue('lunch', 'fruit');

        $this->assertEquals('fruit', $this->instance->lunch);
    }

    public function testGetServices(): void
    {
        $services = $this->instance->getServices();

        $matches = [
            0 => 'foo',
            1 => 'cookie',
            2 => 'cat',
            3 => 'food',
            4 => 'water',
            5 => 'sky',
            6 => 'factory',
            7 => 'lunch',
        ];

        $this->assertEquals($matches, $services);
    }

    public function test__isset(): void
    {
        $this->assertTrue(isset($this->instance->foo));
        $this->assertFalse(isset($this->instance->nope));
    }

    public function testIsset(): void
    {
        $this->assertTrue($this->instance->isset('foo'));
        $this->assertFalse($this->instance->isset('nope'));
    }

    public function testHas(): void
    {
        $this->assertTrue($this->instance->has('foo'));
        $this->assertTrue($this->instance->has('sky'));
        $this->assertFalse($this->instance->has('nope'));
    }

    public function test__unset(): void
    {
        unset($this->instance->foo);

        $this->assertFalse($this->instance->has('foo'));
    }

    public function testUnset(): void
    {
        $this->instance->unset('foo');

        $this->assertFalse($this->instance->has('foo'));
    }

    public function testRemove(): void
    {
        $this->instance->remove('foo');

        $this->assertFalse($this->instance->has('foo'));
    }

    public function testServiceNotFoundException(): void 
    {
        $this->expectException(ServiceNotFound::class);

        $this->instance->get('bogus service');
    }

}
