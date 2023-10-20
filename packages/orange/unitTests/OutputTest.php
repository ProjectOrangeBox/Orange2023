<?php

declare(strict_types=1);

use dmyers\orange\Output;
use dmyers\orange\exceptions\Output as OutputException;

final class OutputTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'cookie' => [
                'domain' => '',
                'path' => '',
                'secure' => '',
                'httponly' => '',
                'samesite' => '',
            ]
        ]);
    }

    // Tests
    public function testFlush(): void
    {
        $this->instance->set('this is the output');

        $this->assertEquals('this is the output', $this->instance->get());

        $this->instance->flush();

        $this->assertEquals('', $this->instance->get());
    }

    public function testSetOutput(): void
    {
        $this->instance->set('this is the output');

        $this->assertEquals('this is the output', $this->instance->get());
    }

    public function testAppendOutput(): void
    {
        $this->instance->set('this is the output');
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
        $this->instance->header('HTTP/1.1 404 Not Found');

        $this->assertContains('HTTP/1.1 404 Not Found', $this->instance->getHeaders());
    }

    public function testGetHeaders(): void
    {
        $this->assertEquals([], $this->instance->getHeaders());
    }

    public function testSendHeaders(): void
    {
        $this->instance->header('HTTP/1.1 404 Not Found');

        $this->instance->sendHeaders();

        $this->assertContains('HTTP/1.1 404 Not Found', $this->getPrivatePublic('headers'));
    }

    public function testFlushHeaders(): void
    {
        $this->instance->header('HTTP/1.1 404 Not Found');

        $this->instance->flushHeaders();

        $this->assertNotContains('HTTP/1.1 404 Not Found', $this->instance->getHeaders());
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

        $this->instance->set($html);

        ob_start();
        $this->instance->send();
        $output = ob_get_clean();

        $this->assertEquals($html, $output);
        $this->assertEquals($html, $this->instance->get());
        $this->assertEquals(200, $this->instance->getResponseCode());
        $this->assertContains('Content-Type: text/html; charset=utf-8',  $this->getPrivatePublic('headers'));
    }

    public function testRedirect(): void
    {
        ob_start();
        $this->instance->redirect('http://www.example.com', 308, false);
        $output = ob_get_clean();

        $this->assertEquals(308,  $this->getPrivatePublic('statusCode'));
        $this->assertContains('Location: http://www.example.com',  $this->getPrivatePublic('headers'));
        $this->assertEquals('', $output);
    }

    public function testFlushAll(): void
    {
        $this->instance->header('HTTP/1.1 404 Not Found');
        $this->instance->set('hello world');

        $this->instance->flushAll();

        $this->assertEmpty($this->getPrivatePublic('headers'));
        $this->assertEmpty($this->getPrivatePublic('cookies'));
        $this->assertEquals('', $this->getPrivatePublic('output'));
    }

    public function testHeaderSentFlushException(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);

        $this->instance->sendHeaders();

        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Headers already sent.');

        $this->instance->flushHeaders();
    }

    public function testHeaderSentException(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);

        $this->instance->sendHeaders();

        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Headers already sent.');

        $this->instance->sendHeaders();
    }

    public function testResponseCodeException(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ]);

        $this->instance->sendResponseCode();

        $this->expectException(OutputException::class);
        $this->expectExceptionMessage('Response Code already sent.');

        $this->instance->responseCode(404);
    }

    public function testCookie(): void
    {
        $minutes = 600;
        $expire = time() + $minutes;

        $this->instance->cookie('username', 'Johnny Appleseed', $minutes);

        $this->assertEquals(['username' => ['name' => 'username', 'value' => 'Johnny Appleseed', 'options' => [
            'expires' => $expire,
            'path' => '',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => "Lax",
        ]]], $this->getPrivatePublic('cookies'));
    }

    public function testFlushCookie(): void
    {
        $this->instance->cookie('username', 'Johnny Appleseed', 6000);
        $this->instance->flushCookies();

        $this->assertEmpty($this->getPrivatePublic('cookies'));
    }
}
