<?php

declare(strict_types=1);

use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\Output;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\MethodNotFound;

final class ErrorTest extends unitTestHelper
{
    protected $instance;
    protected $output;

    protected function setUp(): void
    {
        $errorConfig = [
            'view paths' => [
                __DIR__ . '/support/views',
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
            'default key' => 'default',
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
            'show already sent error' => false,
            'simulate' => true,
        ];

        $this->output = new Output($outputConfig);

        $this->instance = new Error($errorConfig, new View($viewConfig, new Data([])), $this->output);
    }

    // Tests
    public function testRequestType(): void
    {
        $this->instance->requestType('cli');

        $this->assertEquals('cli', $this->getPrivatePublic('requestType'));
    }

    public function testRequestTypeException(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Unknown type "monkeys".');

        $this->instance->requestType('monkeys');
    }

    public function testAdd(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->assertEquals([0 => 'This is a bad error.', 1 => 'This is a another bad error.'], $this->instance->errors('default'));
        $this->assertEquals(['default' => [0 => 'This is a bad error.', 1 => 'This is a another bad error.']], $this->instance->errors());
    }

    public function testCollectErrors(): void
    {
        include_once __DIR__ . '/support/collectErrorsFromMe.php';

        $object = new collectErrorsFromMe();

        $this->instance->collectErrors($object, 'people');

        $this->assertEquals([0 => 'error 1', 1 => 'error 2'], $this->instance->errors('people'));
        $this->assertEquals(['people' => [0 => 'error 1', 1 => 'error 2']], $this->instance->errors());
    }

    public function testCollectErrorsException(): void
    {
        include_once __DIR__ . '/support/mockRouter.php';

        $object = new mockRouter([]);

        $this->expectException(MethodNotFound::class);
        $this->expectExceptionMessage('Errors could not collect from "mockRouter" because it does not have a errors method.');

        $this->instance->collectErrors($object);
    }

    public function testClear(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->instance->clear();

        $this->assertEquals([], $this->instance->errors());
    }

    public function testReset(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->instance->clear();

        $this->assertEquals([], $this->instance->errors());
        $this->assertEquals('html', $this->getPrivatePublic('requestType'));
    }

    public function testHas(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->assertTrue($this->instance->has());
        $this->assertTrue($this->instance->has('default'));
        $this->assertFalse($this->instance->has('foobar'));
    }

    public function testSend(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->instance->send(500);

        $this->assertEquals('<p>This is a bad error.</p><p>This is a another bad error.</p>', $this->output->get());
    }

    public function testSendOnError1(): void
    {
        $this->instance->add('This is a bad error.');
        $this->instance->add('This is a another bad error.');

        $this->instance->sendOnError(500);

        $this->assertEquals('<p>This is a bad error.</p><p>This is a another bad error.</p>', $this->output->get());
    }


    public function testSendOnError2(): void
    {
        $this->instance->sendOnError(500);

        $this->assertEquals('', $this->output->get());
    }

    public function testShowError(): void
    {
        $this->instance->showError('Oh Darn!');

        $this->assertEquals('<h1>An Error Was Encountered<h1><p>Oh Darn!</p>', $this->output->get());
    }

    public function testDisplay(): void
    {
        $this->instance->display('error', ['heading' => 'An Error Was Encountered', 'message' => 'Oh Darn!'], 500);

        $this->assertEquals('<h1>An Error Was Encountered<h1><p>Oh Darn!</p>', $this->output->get());
    }
}
