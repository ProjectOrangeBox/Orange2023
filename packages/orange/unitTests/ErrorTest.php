<?php

declare(strict_types=1);

use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\stubs\Output;
use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $errorConfig = [
            'view paths' => [
                __DIR__.'/support/views',
            ],
            'types' => [
                'cli' => [
                    'subfolder' => '/cli',
                    'mime type' => 'text/plain',
                    'charset' => 'utf-8',
                ],
                'ajax' => [
                    'subfolder' => '/ajax',
                    'mime type' => 'application/json',
                    'charset' => 'utf-8',
                ],
                'html' => [
                    'subfolder' => '/html',
                    'mime type' => 'text/html',
                    'charset' => 'utf-8',
                ],
            ],
            // default - this is overridden by the input class on instantiation
            'request type' => 'html',
            'default error view' => 'error',
            'default status code' => 500,
        ];

        $viewConfig = [
            'view paths' => [],
            'view aliases' => [],
            'temp folder' => sys_get_temp_dir(),
            'debug' => false,
            'extension' => '.php',
        ];

        $outputConfig = [
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show header error' => false,
        ];

        $this->instance = new Error($errorConfig, new View($viewConfig, new Data([])), new Output($outputConfig));

        print_r($this->instance->__debugInfo());
    }

    // Tests
    public function testRequestType(): void
    {
        $this->assertTrue(true);
    }

    public function testAdd(): void
    {
        $this->assertTrue(true);
    }

    public function testCollectErrors(): void
    {
        $this->assertTrue(true);
    }

    public function testClear(): void
    {
        $this->assertTrue(true);
    }

    public function testReset(): void
    {
        $this->assertTrue(true);
    }

    public function testHas(): void
    {
        $this->assertTrue(true);
    }

    public function testErrors(): void
    {
        $this->assertTrue(true);
    }

    public function testSend(): void
    {
        $this->assertTrue(true);
    }

    public function testSendOnError(): void
    {
        $this->assertTrue(true);
    }

    public function testShowError(): void
    {
        $this->assertTrue(true);
    }

    public function testDisplay(): void
    {
        $this->assertTrue(true);
    }
}
