<?php

declare(strict_types=1);

use dmyers\orange\Log;
use dmyers\orange\Config;
use dmyers\orange\Container;
use dmyers\orange\exceptions\InvalidConfigurationValue;

final class HelpersTest extends unitTestHelper
{
    private $testFile = '';

    protected function setUp(): void
    {
        require __DIR__ . '/../src/helpers.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    // Tests
    public function testContainer(): void
    {
        $this->assertInstanceOf(Container::class, container());
    }

    public function testMergeDefaultConfig(): void
    {
        $config = [
            'name' => 'Jenny Appleseed',
            'age' => 23,
            'food' => 'cookie',
        ];

        $match = [
            'name' => 'Jenny Appleseed',
            'age' => 23,
            'food' => 'cookie',
            'example' => 2
        ];

        $this->assertEquals($match, mergeDefaultConfig($config, __DIR__ . '/support/configExample2.php'));
    }

    public function testLogMsg(): void
    {
        $this->testFile = __DIR__ . '/support/writeable/test-log.txt';
        
        // setup config
        $log = new Log([
            'filepath' => $this->testFile,
            'threshold' => 255,
            'line format' => '12:00am %level %message',
            'timestamp format' => 'Y-m-d H:i:s',
        ]);

        // get an instance of container
        $container = Container::getInstance();

        // add config to container as config service
        $container->set('log', $log);

        // test away!
        logMsg(LOG::ALERT,'This is an Alert!');

        $this->assertEquals('12:00am ALERT This is an Alert!',file_get_contents($this->testFile));
    }

    public function testMergeEnv(): void
    {
        mergeEnv(__DIR__ . '/support/mockEnv');
        $this->assertEquals('development', fetchEnv('ENVIRONMENT'));
        $this->assertEquals(true, fetchEnv('DEBUG'));
    }

    public function testFetchEnv(): void
    {
        $matches = [
            'map' => true,
            'cookies' => 'sugar',
        ];

        $this->assertEquals($matches, appEnv($matches));
        $this->assertEquals(true, fetchEnv('map'));
        $this->assertEquals('sugar', fetchEnv('cookies'));
        $this->assertEquals('default value', fetchEnv('food', 'default value'));
    }

    public function testFetchEnvDefaultException(): void
    {
        $this->expectException(InvalidConfigurationValue::class);
        $this->expectExceptionMessage('No env value found for "food" and no default value set.');

        $this->assertEquals(null, fetchEnv('food'));
    }

    public function testAppEnv(): void
    {
        $matches = [
            'map' => true,
            'cookies' => 'sugar',
        ];

        $this->assertEquals($matches, appEnv($matches));
        $this->assertEquals($matches, appEnv());
    }

    public function testFile_put_contents_atomic(): void
    {
        $testFile = __DIR__ . '/support/writeable/test.txt';

        $this->assertEquals(6, file_put_contents_atomic($testFile, 'foobar'));
    }

    public function testConcat(): void
    {
        $this->assertEquals('Johnny.Appleseed', concat('Johnny', '.', 'Appleseed'));
    }

    public function testGetDotNotation(): void
    {
        $array = [
            'names' => [
                'first' => 'John',
                'last' => 'Apple',
            ],
            'age' => 28,
        ];

        $this->assertEquals('John', getDotNotation($array, 'names.first'));
        $this->assertEquals('none', getDotNotation($array, 'names.middle', 'none'));

        $this->assertEquals('John', getDotNotation($array, 'names/first', null, '/'));
        $this->assertEquals('none', getDotNotation($array, 'names/middle', 'none', '/'));
    }

    public function testConfig(): void
    {
        // setup config
        $config = new Config(['config folder' => __DIR__ . '/support/env']);

        // get an instance of container
        $container = Container::getInstance();

        // add config to container as config service
        $container->set('config', $config);

        // test away!
        $this->assertEquals('Jenny Appleseed', config('configexample2', 'name'));
        $this->assertEquals('', config('configexample2', 'dummy'));
        $this->assertEquals('bar', config('configexample2', 'foo', 'bar'));
    }
}
