<?php

declare(strict_types=1);

use dmyers\orange\Log;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
    private $instance;
    private $config = [
        'filepath' => __DIR__ . '/support/log.txt',
        'threshold' => 128,
        'permissions' => 0777,
    ];

    protected function setUp(): void
    {
        if (file_exists($this->config['filepath'])) {
            unlink($this->config['filepath']);
        }

        $this->instance = new Log($this->config);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->config['filepath'])) {
            unlink($this->config['filepath']);
        }
    }

    // Tests
    public function testChangeThreshold(): void
    {
        $this->instance->changeThreshold(0);

        $this->assertEquals(0, $this->instance->getThreshold());

        $this->instance->changeThreshold(255);

        $this->assertEquals(255, $this->instance->getThreshold());
    }

    public function testGetThreshold(): void
    {
        $this->instance->changeThreshold(255);

        $this->assertEquals(255, $this->instance->getThreshold());
    }

    public function testIsEnabled(): void
    {
        $this->instance->changeThreshold(0);

        $this->assertFalse($this->instance->isEnabled());

        $this->instance->changeThreshold(255);

        $this->assertTrue($this->instance->isEnabled());
    }

    public function test__call(): void
    {
        $this->instance->changeThreshold(255);

        $this->instance->emergency('This is an emergency');
        $this->instance->notice('This is an notice 111');
        
        $this->assertFileExists($this->config['filepath']);

        $this->assertStringContainsString('This is an emergency', file_get_contents($this->config['filepath']));
        $this->assertStringContainsString('This is an notice 111', file_get_contents($this->config['filepath']));

        // disable notice
        $this->instance->changeThreshold(223);

        $this->instance->notice('This is an notice 222');

        $this->assertStringNotContainsString('This is an notice 222', file_get_contents($this->config['filepath']));
    }
}
