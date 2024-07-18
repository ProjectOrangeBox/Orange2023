<?php

declare(strict_types=1);

namespace orange\framework\stubs;

use orange\framework\Output as OrangeOutput;
use orange\framework\interfaces\OutputInterface;

class Output extends OrangeOutput implements OutputInterface
{
    // attached "output" from the php functions to a shared array
    // instead of sending it "out"
    // you can then read test if needed
    public array $test = [];
    public bool $headersSent = false;

    protected function echo(string $string): void
    {
        $this->test['echo'][] = $string;
    }

    protected function exit(int $status = 0): void
    {
        $this->test['exit'][] = $status;
    }

    protected function headersSent(): bool
    {
        return $this->headersSent;
    }

    protected function sendheader(string $header): void
    {
        $this->test['header'][] = $header;

        $this->headersSent = true;
    }

    protected function httpResponseCode(int $response_code = 0): void
    {
        $this->test['http_response_code'][] = $response_code;
    }

    protected function setCookie(string $name, string $value = '', $options = 0): bool
    {
        $this->test['setCookie'][] = [$name, $value, $options];

        return true;
    }
}
