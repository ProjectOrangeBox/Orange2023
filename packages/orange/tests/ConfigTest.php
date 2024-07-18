<?php

declare(strict_types=1);

use orange\framework\Config;
use orange\framework\DirectorySearch;
use orange\framework\exceptions\config\InvalidConfigurationValue;

final class ConfigTest extends unitTestHelper
{
    protected $instance;

    protected $d1 = __DIR__ . '/support/directorySearch/foo';
    protected $d2 = __DIR__ . '/support/directorySearch/bar';
    protected $d3 = __DIR__ . '/support/directorySearch';
    protected $d4 = __DIR__ . '/support';
    protected $d5 = __DIR__ . '/support/env';

    protected function setUp(): void
    {
        $this->instance = Config::getInstance([]);
    }

    // Tests
    public function testAddPath(): void
    {
        $this->instance->search->addDirectory($this->d1);
        $this->assertEquals([$this->d1], $this->instance->search->listDirectories());

        $this->instance->search->addDirectory($this->d2);
        $this->assertEquals([$this->d2, $this->d1], $this->instance->search->listDirectories());

        $this->instance->search->addDirectory($this->d3, DirectorySearch::LAST);
        $this->assertEquals([$this->d2, $this->d1, $this->d3], $this->instance->search->listDirectories());

        $this->instance->search->addDirectory($this->d4, DirectorySearch::FIRST);
        $this->assertEquals([$this->d4, $this->d2, $this->d1, $this->d3], $this->instance->search->listDirectories());
    }

    public function testGet(): void
    {
        $this->instance->search->addDirectory($this->d4);

        $config = $this->instance->get('configExample1');

        $this->assertEquals($config['name'], 'Johnny Appleseed 2');
        $this->assertEquals($config['age'], 22);
        $this->assertEquals($config['example'], 33);

        $config = $this->instance->get('configExample2');

        $this->assertEquals($config['name'], 'Johnny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 2);

        // this folder will be merged 
        $this->instance->search->unlock()->addDirectory($this->d5);

        $config = $this->instance->get('configExample2');

        $this->assertEquals($config['name'], 'Jenny Appleseed');
        $this->assertEquals($config['age'], 21);
        $this->assertEquals($config['example'], 2);
    }

    public function testInvalidConfigFile(): void
    {
        $this->instance->search->addDirectory(__DIR__ . '/support', true);

        $this->expectException(InvalidConfigurationValue::class);

        $this->instance->get('badConfig');
    }

    public function testConfigNotFound(): void
    {
        $this->instance->search->addDirectory(__DIR__ . '/support', true);

        $this->assertEquals([], $this->instance->get('notArealFile'));
    }
}
