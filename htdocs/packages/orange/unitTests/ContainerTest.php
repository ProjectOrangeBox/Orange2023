<?php

declare(strict_types=1);

use dmyers\orange\Container;
use dmyers\orange\exceptions\ServiceNotFound;
use dmyers\orange\interfaces\ContainerInterface;

final class ContainerTest extends unitTestHelper
{
    protected $instance;
    protected $services;

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
        $this->assertInstanceOf(Container::class, $this->instance->setServices($this->services));
    }

    public function test__get(): void
    {
        $this->instance->setServices($this->services);

        $this->assertEquals('bar', $this->instance->foo);
        $this->assertEquals('bar', $this->instance->cat);
        $this->assertInstanceOf(stdClass::class, $this->instance->cookie);
    }

    public function testGet(): void
    {
        $this->instance->setServices($this->services);

        $this->assertEquals('bar', $this->instance->get('foo'));
        $this->assertEquals('bar', $this->instance->get('cat'));
        $this->assertInstanceOf(stdClass::class, $this->instance->get('cookie'));
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
        // add water
        $this->instance->water = 'abc123';
        
        // public function addAlias(string $alias, string $serviceName): self
        $this->instance->addAlias('sky','water');

        // ask for sky
        $this->assertEquals('abc123', $this->instance->sky);
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


    public function test__isset(): void
    {
        $this->instance->setServices($this->services);

        $this->assertTrue(isset($this->instance->foo));
        $this->assertFalse(isset($this->instance->nope));
    }

    public function testIsset(): void
    {
        $this->instance->setServices($this->services);

        $this->assertTrue($this->instance->isset('foo'));
        $this->assertFalse($this->instance->isset('nope'));
    }

    public function testHas(): void
    {
        $this->instance->setServices($this->services);

        $this->assertTrue($this->instance->has('foo'));
        $this->assertTrue($this->instance->has('cookie'));
        $this->assertFalse($this->instance->has('invalid'));
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
        $this->expectExceptionMessage('bogus service');

        $this->instance->get('bogus service');
    }
}
