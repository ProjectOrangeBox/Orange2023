<?php

declare(strict_types=1);

use dmyers\orange\Output;
use dmyers\orange\exceptions\Output as ExceptionsOutput;

final class OutputTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show already sent error' => false,
            'simulate' => true,
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
        $this->instance->append(' this too!');

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

    public function testHeader(): void
    {
        $this->instance->header('HTTP/1.1 404 Not Found');

        $this->assertContains('HTTP/1.1 404 Not Found', $this->instance->getHeaders());
    }

    public function testGetHeaders(): void
    {
        $this->assertContains('Content-Type: text/html; charset=utf-8', $this->instance->getHeaders());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendHeaders(): void
    {
        $this->instance->header('HTTP/1.1 404 Not Found');

        $this->instance->sendHeaders();

        $this->assertContains('HTTP/1.1 404 Not Found', $this->getPrivatePublic('sentHeaders'));
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

    public function testResponseCode(): void
    {
        $this->instance->responseCode(500);

        $this->assertEquals(500, $this->instance->getResponseCode());
    }

    public function testGetResponseCode(): void
    {
        $this->assertEquals(200, $this->instance->getResponseCode());
    }

    public function testSendResponseCode(): void
    {
        $this->instance->sendResponseCode();

        $this->assertEquals(200, $this->getPrivatePublic('sentCode'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSend(): void
    {
        $this->instance->set('this is the output');

        $this->instance->send();

        $this->assertEquals(200,  $this->getPrivatePublic('sentCode'));
        $this->assertContains('Content-Type: text/html; charset=utf-8',  $this->getPrivatePublic('sentHeaders'));
        $this->assertEquals('this is the output', $this->instance->get());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedirect(): void
    {
        ob_start();
        $this->instance->redirect('http://www.example.com', 123, false);
        $output = ob_get_clean();

        $this->assertEquals(123,  $this->getPrivatePublic('sentCode'));
        $this->assertContains('Location: http://www.example.com',  $this->getPrivatePublic('sentHeaders'));
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
            'show already sent error' => true,
            'simulate' => true,
        ]);

        $this->instance->sendHeaders();

        $this->expectException(ExceptionsOutput::class);
        $this->expectExceptionMessage('Content has already been sent therefore headers cannot be flushed at this time.');

        $this->instance->flushHeaders();
    }

    public function testHeaderSentException(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show already sent error' => true,
            'simulate' => true,
        ]);

        $this->instance->sendHeaders();

        $this->expectException(ExceptionsOutput::class);
        $this->expectExceptionMessage('Content has already been sent therefore headers cannot be sent at this time.');

        $this->instance->sendHeaders();
    }


    public function testResponseCodeException(): void
    {
        $this->instance = new Output([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
            'show already sent error' => true,
            'simulate' => true,
        ]);

        $this->instance->sendResponseCode();

        $this->expectException(ExceptionsOutput::class);
        $this->expectExceptionMessage('Response Code Already Sent.');

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
