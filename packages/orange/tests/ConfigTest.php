<?php

declare(strict_types=1);

use orange\framework\Config;
use orange\framework\exceptions\InvalidConfigurationValue;

final class ConfigTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Config::getInstance([]);
    }

    // Tests
    public function testAddPath(): void
    {
        $this->instance->configSearch->addDirectory('/foo/bar');
        $this->assertEquals($this->instance->configSearch->list(), ['/foo/bar']);

        $this->instance->configSearch->addDirectory('/foo/foo');
        $this->assertEquals($this->instance->configSearch->list(), ['/foo/bar', '/foo/foo']);

        $this->instance->configSearch->addDirectory('/bar/foo', true);
        $this->assertEquals($this->instance->configSearch->list(), ['/bar/foo', '/foo/bar', '/foo/foo']);
    }

    public function testMagicGet(): void
    {
        $this->instance->configSearch->addDirectory(__DIR__ . '/support', true);

        $config = $this->instance->__get('configexample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 1);

        $this->instance->configSearch->addDirectory(__DIR__ . '/support/env');

        $config = $this->instance->__get('configexample2');

        $this->assertEquals($config['name'], 'Jenny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 2);
    }

    public function testGet(): void
    {
        $this->instance->configSearch->addDirectory(__DIR__ . '/support', true);

        $name = $this->instance->get('configexample1', 'name');

        $this->assertEquals($name, 'Johnny Appleseed');

        $this->instance->configSearch->addDirectory(__DIR__ . '/support/env');

        $name = $this->instance->get('configexample2', 'name');

        $this->assertEquals($name, 'Jenny Appleseed');
    }

    public function testBadConfigException(): void
    {
        $this->instance->configSearch->addDirectory(__DIR__ . '/support', true);

        $this->expectException(InvalidConfigurationValue::class);

        $this->instance->get('badConfig', 'name');
    }
}
