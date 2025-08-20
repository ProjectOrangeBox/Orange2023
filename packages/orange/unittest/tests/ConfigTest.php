<?php

declare(strict_types=1);

use orange\framework\Config;

final class ConfigTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Config::getInstance([
            WORKINGDIR . '/config',
            WORKINGDIR . '/config/testing',
        ]);
    }

    // Tests
    public function testGet(): void
    {
        $config = $this->instance->get('aaa');

        $this->assertEquals($config['color'], 'blue');
        $this->assertEquals($config['age'], 23);
        $this->assertEquals($config['size'], 'large');

        $config = $this->instance->get('bbb');

        $this->assertEquals($config['color'], 'green');
        $this->assertEquals($config['age'], 33);
        $this->assertEquals($config['size'], 'small');
    }

    public function testGetKey(): void
    {
        $this->assertEquals($this->instance->get('aaa', 'color'), 'blue');
        $this->assertEquals($this->instance->get('aaa', 'age'), '23');
        $this->assertEquals($this->instance->get('bbb', 'color'), 'green');
    }

    public function testM_M_Get(): void
    {
        $config = $this->instance->aaa;

        $this->assertEquals($config['color'], 'blue');
        $this->assertEquals($config['age'], 23);
        $this->assertEquals($config['size'], 'large');

        $config = $this->instance->bbb;

        $this->assertEquals($config['color'], 'green');
        $this->assertEquals($config['age'], 33);
        $this->assertEquals($config['size'], 'small');
    }

    public function testInvalidConfigFile(): void
    {
        $config = $this->instance->get('ccc');

        $this->assertEquals($config, []);
    }
}
