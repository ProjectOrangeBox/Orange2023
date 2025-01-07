<?php

declare(strict_types=1);

use peels\cookie\Cookie;
use orange\framework\Input;
use orange\framework\Output;

final class CookieTest extends unitTestHelper
{
    protected $instance;
    protected $input;
    protected $output;

    protected function setUp(): void
    {
        $this->input = Input::getInstance([
            'cookie' => ['foo' => 'bar'],
        ]);
        $this->output = Output::getInstance([
            'contentType' => 'text/html',
            'charSet' => 'utf-8',
        ], $this->input);

        $this->instance = Cookie::getInstance([], $this->input, $this->output);
    }

    public function testSet(): void
    {
        $this->instance->set('name', 'value');
        $this->assertEquals(['HTTP/1.0 200 OK', 'Content-Type: text/html; charset=UTF-8', 'Set-Cookie: name=value'], $this->output->getHeaders());

        $expires = 60;

        $this->instance->set('session', 'abc123', $expires, '/', 'foobar.com', true, false);

        $this->assertEquals(['HTTP/1.0 200 OK', 'Content-Type: text/html; charset=UTF-8', 'Set-Cookie: name=value', 'Set-Cookie: session=abc123; expires=' . gmdate('D, d-M-Y H:i:s T', time() + $expires) . '; Max-Age=' . $expires - time() . '; path=/; domain=foobar.com; secure'], $this->output->getHeaders());
    }

    public function testGet(): void
    {
        $this->assertEquals('bar', $this->instance->get('foo'));
    }

    public function testHas(): void
    {
        $this->assertTrue($this->instance->has('foo'));
        $this->assertFalse($this->instance->has('nofoo'));
    }

    public function testRemove(): void
    {
        $this->instance->set('session', 'abc123', 60, '/', 'foobar.com', true, false);
        $this->instance->remove('session');

        $expires = 0;

        $this->assertEquals(['HTTP/1.0 200 OK', 'Content-Type: text/html; charset=UTF-8', 'Set-Cookie: session=deleted; expires=' . gmdate('D, d-M-Y H:i:s T', $expires + time()) . '; Max-Age=' . $expires - time()], $this->output->getHeaders());
    }
}
