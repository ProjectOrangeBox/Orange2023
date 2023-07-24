<?php

declare(strict_types=1);

use dmyers\orange\Config;
use PHPUnit\Framework\TestCase;
use dmyers\orange\exceptions\InvalidConfigurationValue;

final class ConfigTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new Config([
            'skip defaults' => true,
        ]);
    }

    // Tests
    public function testAddPath(): void
    {
        $this->instance->addPath('/foo/bar');

        $vars = $this->instance->__debugInfo()['searchPaths'];

        $this->assertEquals($vars[0], '/foo/bar');

        $this->instance->addPath('/bar/foo', true);

        $vars = $this->instance->__debugInfo()['searchPaths'];

        $this->assertEquals($vars[0], '/bar/foo');
        $this->assertEquals($vars[1], '/foo/bar');
    }

    public function testMagicGet(): void
    {
        $this->instance->addPath(__DIR__ . '/support', true);

        $config = $this->instance->__get('configexample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 1);

        $this->instance->addPath(__DIR__ . '/support/env');

        $config = $this->instance->__get('configexample2');

        $this->assertEquals($config['name'], 'Jenny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 2   );
    }

    public function testMagicSet(): void
    {
        $this->instance->addPath(__DIR__ . '/support', true);

        $config = $this->instance->__get('configexample1');

        $config['food'] = 'Pizza';

        $this->instance->__set('configexample1', $config);

        $config = $this->instance->__get('configexample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['food'], 'Pizza');
    }

    public function testGet(): void
    {
        $this->instance->addPath(__DIR__ . '/support', true);

        $name = $this->instance->get('configexample1','name');

        $this->assertEquals($name, 'Johnny Appleseed');

        $this->instance->addPath(__DIR__ . '/support/env');

        $name = $this->instance->get('configexample2','name');

        $this->assertEquals($name, 'Jenny Appleseed');
    }

    public function testSet(): void
    {
        $this->instance->set('configexample1', 'color', 'red');

        $config = $this->instance->get('configexample1');

        $this->assertEquals($config['color'], 'red');
    }

    public function testBadConfigException(): void 
    {
        $this->instance->addPath(__DIR__ . '/support', true);

        $this->expectException(InvalidConfigurationValue::class);

        $name = $this->instance->get('badConfig','name');
    }

}
