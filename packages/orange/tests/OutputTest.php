<?php

declare(strict_types=1);

use orange\framework\Output;
use orange\framework\exceptions\output\Output as OutputException;

final class OutputTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Output::getInstance([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);
    }

    // Tests
    public function testFlush(): void
    {
        $this->instance->write('this is the output');

        $this->assertEquals('this is the output', $this->instance->get());

        $this->instance->flush();

        $this->assertEquals('', $this->instance->get());
    }

    public function testSetOutput(): void
    {
        $this->instance->write('this is the output');

        $this->assertEquals('this is the output', $this->instance->get());
    }

    public function testAppendOutput(): void
    {
        $this->instance->write('this is the output');
        $this->instance->write(' this too!');

        $this->assertEquals('this is the output this too!', $this->instance->get());
    }

    public function testGetOutput(): void
    {
        $this->assertEquals('', $this->instance->get());
    }

    public function testContentType(): void
    {
        $this->instance->contentType('application/json');

        $this->assertEquals('application/json', $this->instance->getContentType());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('text/html', $this->instance->getContentType());
    }

    public function testGetContentTypeShortHand(): void
    {
        $this->instance->contentType('dot');

        $this->assertEquals('text/vnd.graphviz', $this->instance->getContentType());
    }

    public function testHeader(): void
    {
        $this->instance->header('Cache-Control: max-age=604800');

        $this->assertContains('Cache-Control: max-age=604800', $this->instance->getHeaders());
    }

    public function testGetHeaders(): void
    {
        $this->assertEquals([], $this->instance->getHeaders());
    }

    public function testSendHeaders(): void
    {
        $this->instance->header('Cache-Control: max-age=604800');

        $this->instance->sendHeaders();

        $this->assertContains('Cache-Control: max-age=604800', $this->instance->getHeaders());
    }

    public function testFlushHeaders(): void
    {
        $this->instance->header('Cache-Control: max-age=604800');

        $this->instance->flushHeaders();

        $this->assertNotContains('Cache-Control: max-age=604800', $this->instance->getHeaders());
    }

    public function testCharSet(): void
    {
        $this->instance->charSet('ASCII');

        $this->assertEquals('ASCII', $this->instance->getCharSet());
    }

    public function testGetCharSet(): void
    {
        $this->assertEquals('utf-8', $this->instance->getCharSet());
    }

    public function testResponseCodeInt(): void
    {
        $this->instance->responseCode(500);

        $this->assertEquals(500, $this->instance->getResponseCode());
    }

    public function testResponseCodeString(): void
    {
        $this->instance->responseCode('Bad Gateway');

        $this->assertEquals(502, $this->instance->getResponseCode());
    }

    public function testResponseCodeInvalid(): void
    {
        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Unknown HTTP Status Code foobar');

        $this->instance->responseCode('foobar');
    }

    public function testResponseCodeInvalidInt(): void
    {
        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Unknown HTTP Status Code 666');

        $this->instance->responseCode(666);
    }

    public function testGetResponseCode(): void
    {
        $this->assertEquals(200, $this->instance->getResponseCode());
    }

    public function testSendResponseCode(): void
    {
        $this->instance->sendResponseCode();

        $this->assertEquals(200, $this->instance->getResponseCode());
    }

    public function testSend(): void
    {
        $html = '<h1>Hello World!</h1>';

        $this->instance->write($html);

        ob_start();
        $this->instance->send();
        $output = ob_get_clean();

        $this->assertEquals($html, $output);
        $this->assertEquals($html, $this->instance->get());
        $this->assertEquals(200, $this->instance->getResponseCode());
        $this->assertContains('Content-Type: text/html; charset=utf-8', $this->instance->getHeaders());
    }

    public function testRedirect(): void
    {
        ob_start();
        $this->instance->redirect('http://www.example.com', 308, false);
        $output = ob_get_clean();

        $this->assertEquals(308, $this->getPrivatePublic('statusCode'));
        $this->assertContains('Location: http://www.example.com', $this->instance->getHeaders());
        $this->assertEquals('', $output);
    }

    public function testFlushAll(): void
    {
        $this->instance->header('Content-Type: text/html; charset=utf-8');
        $this->instance->write('hello world');

        $this->instance->flushAll();

        $this->assertEmpty($this->getPrivatePublic('headers'));
        $this->assertEquals('', $this->getPrivatePublic('output'));
    }

    public function testHeaderSentFlushException(): void
    {
        $this->instance = Output::getInstance([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);

        $this->instance->sendHeaders();

        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Some headers already sent.');

        $this->instance->flushHeaders();
    }

    public function testResponseCodeException(): void
    {
        $this->instance = Output::getInstance([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);

        $this->instance->send();

        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Status response code sent.');

        $this->instance->responseCode(404);
    }
}
