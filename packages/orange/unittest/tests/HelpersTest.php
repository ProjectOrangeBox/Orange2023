<?php

declare(strict_types=1);

use orange\framework\Log;
use orange\framework\Config;
use orange\framework\Application;
use orange\framework\Container;

final class HelpersTest extends unitTestHelper
{
    private $testFile = '';

    protected function setUp(): void
    {
        require_once ORANGEDIR . '/helpers/Ary.php';
        require_once ORANGEDIR . '/helpers/Dot.php';
        require_once ORANGEDIR . '/helpers/errors.php';
        require_once ORANGEDIR . '/helpers/helpers.php';
        require_once ORANGEDIR . '/helpers/wrappers.php';
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

    public function testLogMsg(): void
    {
        $this->testFile = WORKINGDIR . '/writeable/test-log.txt';

        // setup config
        $log = Log::getInstance([
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
        logMsg(LOG::ALERT, 'This is an Alert!');

        $this->assertEquals('12:00am ALERT This is an Alert!', file_get_contents($this->testFile));
    }

    public function testFile_put_contents_atomic(): void
    {
        $testFile = WORKINGDIR . '/writeable/test.txt';

        $this->assertEquals(6, file_put_contents_atomic($testFile, 'foobar'));
    }

    public function testConcat(): void
    {
        $this->assertEquals('Johnny.Appleseed', concat('Johnny', '.', 'Appleseed'));
    }

    public function testConfig(): void
    {
        // setup config
        $config = Config::getInstance([WORKINGDIR . '/env']);

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
