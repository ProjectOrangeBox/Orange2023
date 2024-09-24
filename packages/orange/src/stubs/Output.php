<?php

declare(strict_types=1);

namespace orange\framework\stubs;

use orange\framework\Output as RealOutput;
use orange\framework\interfaces\OutputInterface;

/**
 * when you request this stub it will automatically load the config from the config
 * folder 1 level below it because that is what the parent class (orange\framework\Output) does.
 * Those config files include:
 *   mimes.php
 *   output.php
 *   statusCodes.php
 *
 * @package orange\framework\stubs
 */

class Output extends RealOutput implements OutputInterface
{
    // attached "output" from the php functions to a shared array
    // instead of sending it "out"
    // you can then read test if needed
    public array $test = [];
    public bool $headerSent = false;

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
        return $this->headerSent;
    }

    protected function sendheader(string $header): void
    {
        $this->test['header'][] = $header;

        $this->headerSent = true;
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
