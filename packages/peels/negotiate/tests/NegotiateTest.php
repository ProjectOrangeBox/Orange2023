<?php

declare(strict_types=1);

use orange\framework\Input;
use peels\negotiate\Negotiate;

final class NegotiateTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = Negotiate::getInstance(Input::getInstance([]));
    }

    public function testCanAccept(): void
    {
        $this->assertEquals('utf-8', $this->instance->charset(['foo']));
    }

    public function testCanLanguage(): void
    {
        $this->assertEquals('en-US', $this->instance->language(['en-US']));
    }

    public function testCanEncoding(): void
    {
        $this->assertEquals('en-US', $this->instance->encoding(['en-US']));
    }

    public function testCanMedia(): void
    {
        $this->assertEquals('en-US', $this->instance->media(['en-US']));
    }
}
