<?php

declare(strict_types=1);

use dmyers\orange\Input;
use dmyers\orange\Config;
use dmyers\orange\Dispatcher;
use dmyers\orange\stubs\Output;
use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    private $instance;

    protected function setUp(): void
    {
        $config = Config::getInstance([
            'environment' => $_ENV['ENVIRONMENT'],
            'debug' => $_ENV['DEBUG'],
        ]);

        $input = Input::getInstance([]);

        $output = Output::getInstance([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show header error' => false,
        ]);

        $this->instance = Dispatcher::getInstance($input, $output, $config);
    }

    protected function tearDown(): void
    {
    }

    // Tests
    public function testCall(): void
    {




        $this->assertTrue(true);
    }
}
