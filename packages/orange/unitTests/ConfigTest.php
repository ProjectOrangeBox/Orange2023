<?php

declare(strict_types=1);

use dmyers\orange\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $this->instance = Config::getInstance([]);
    }

    protected function tearDown(): void
    {
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

    public function testGet(): void
    {
        $this->instance->addPath(__DIR__ . '/support', true);

        $config = $this->instance->get('configexample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 1);

        $this->instance->addPath(__DIR__ . '/support/env');

        $config = $this->instance->get('configexample2');

        $this->assertEquals($config['name'], 'Jenny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 2   );
    }

    public function testSet(): void
    {
        $config = $this->instance->get('configexample1');

        $config['food'] = 'Pizza';

        $this->instance->set('configexample1', $config);

        $config = $this->instance->get('configexample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['food'], 'Pizza');
    }

}
