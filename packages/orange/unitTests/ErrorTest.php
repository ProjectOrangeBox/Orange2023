<?php

declare(strict_types=1);

use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\stubs\Output;

final class ErrorTest extends unitTestHelper
{
    protected $instance;
    protected $outputStub;

    protected function setUp(): void
    {
        $errorConfig = [
            'add path' => __DIR__ . '/support/views/errors',
            'request type' => 'html',
            'default root folder' => 'errors',
            'deduplicate' => true,
            'types' => [
                'cli' => [
                    'folder' => '/cli',
                    'mime type' => 'text/plain',
                    'charset' => 'utf-8',
                ],
                'ajax' => [
                    'folder' => '/ajax',
                    'mime type' => 'application/json',
                    'charset' => 'utf-8',
                ],
                'html' => [
                    'folder' => '/html',
                    'mime type' => 'text/html',
                    'charset' => 'utf-8',
                ],
            ],
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
        ];

        // output stub
        $this->outputStub = new Output($outputConfig);

        $this->instance = new Error($errorConfig, new View($viewConfig, new Data([])), $this->outputStub);
    }

    /* Public Method Tests */

    public function testStatusCode(): void
    {
        $this->assertEquals($this->instance, $this->instance->responseCode(302));

        $this->assertEquals(302, $this->getPrivatePublic('responseCode'));
    }

    public function testRequestType(): void
    {
        $this->assertEquals($this->instance, $this->instance->requestType('ajax'));

        $this->assertEquals('ajax', $this->getPrivatePublic('detectedRequestType'));
    }

    public function testMimeType(): void
    {
        $this->assertEquals($this->instance, $this->instance->mimeType('foo/bar'));

        $this->assertEquals('foo/bar', $this->getPrivatePublic('mimeType'));
    }

    public function testCharSet(): void
    {
        $this->assertEquals($this->instance, $this->instance->charSet('UTF-9'));

        $this->assertEquals('UTF-9', $this->getPrivatePublic('charSet'));
    }

    public function testClear(): void
    {
        $this->setPrivatePublic('errors', ['foobar']);

        $this->assertEquals(['foobar'], $this->instance->errors());
        $this->assertEquals($this->instance, $this->instance->clear());
        $this->assertEquals([], $this->instance->errors());
    }

    public function testReset(): void
    {
        $this->setPrivatePublic('errors', ['foobar']);
        $this->setPrivatePublic('statusCode', 500);
        $this->setPrivatePublic('mimeType', 'foo/bar');
        $this->setPrivatePublic('charSet', 'ascii');

        // this sets it back to the detectedRequestType
        // OR
        // what every detectedRequestType is at the time of the call if changed with a call to ->detectedRequestType(...)
        $this->assertEquals($this->instance, $this->instance->reset());

        $this->assertEquals(500, $this->getPrivatePublic('statusCode'));
        $this->assertEquals('html', $this->getPrivatePublic('detectedRequestType'));
        $this->assertEquals('text/html', $this->getPrivatePublic('mimeType'));
        $this->assertEquals('utf-8', $this->getPrivatePublic('charSet'));
        $this->assertEquals([], $this->getPrivatePublic('errors'));
    }

    public function testFolder(): void
    {
        $this->assertEquals($this->instance, $this->instance->folder('/errors/are/here'));

        // auto trims leading and trailing /
        $this->assertEquals('errors/are/here', $this->getPrivatePublic('folder'));
    }

    public function testAdd(): void
    {
        $this->assertEquals($this->instance, $this->instance->add('foobar'));

        $this->assertEquals(['foobar'], $this->instance->errors());
    }

    public function testOnErrorsShow(): void
    {
        $this->assertFalse($this->instance->has());

        $this->instance->onErrorsShow('test');

        $this->assertEquals('', $this->outputStub->get());

        $this->assertEquals($this->instance, $this->instance->add('foobar'));

        $this->instance->onErrorsShow('error');

        $this->assertEquals('<h1>foobar</h1>', $this->outputStub->get());
    }

    public function testShow(): void
    {
        $this->assertEquals($this->instance, $this->instance->add('test access'));
        $this->instance->show('test');

        $this->assertEquals(200, $this->outputStub->http_response_code);
        $this->assertEquals(['Content-Type: text/html; charset=utf-8'], $this->outputStub->header);
        $this->assertEquals('<h1>test access</h1>', $this->outputStub->get());
    }

    public function testShow404(): void
    {
        $this->instance->show404('This is a 404 problem!');

        $this->assertEquals(404, $this->outputStub->http_response_code);
        $this->assertEquals(['Content-Type: text/html; charset=utf-8'], $this->outputStub->header);
        $this->assertEquals('<h1>This is a 404 problem!</h1>', $this->outputStub->get());
    }

    public function testShow500(): void
    {
        $this->instance->show500('This is a 500 problem!');

        $this->assertEquals(500, $this->outputStub->http_response_code);
        $this->assertEquals(['Content-Type: text/html; charset=utf-8'], $this->outputStub->header);
        $this->assertEquals('<h1>This is a 500 problem!</h1>', $this->outputStub->get());
    }

    public function testHas(): void
    {
        $this->assertFalse($this->instance->has());

        $this->setPrivatePublic('errors', ['foobar']);

        $this->assertTrue($this->instance->has());
    }

    public function testErrors(): void
    {
        $this->setPrivatePublic('errors', ['foobar']);

        $this->assertEquals(['foobar'], $this->instance->errors());
    }

    public function testCollectErrors(): void
    {
        require __DIR__ . '/support/collectErrorsFromMe.php';

        $collectErrorsFromMe = new collectErrorsFromMe();

        $this->instance->collectErrors($collectErrorsFromMe);

        $this->assertEquals([['error 1', 'error 2']], $this->instance->errors());

        $this->assertEquals($this->instance, $this->instance->clear());

        $this->instance->collectErrors($collectErrorsFromMe, 'differentMethod');

        $this->assertEquals([['Big Error 1', 'Big Error 2']], $this->instance->errors());
    }
}
