<?php

declare(strict_types=1);

namespace dmyers\orange\stubs;

use dmyers\orange\Output as OrangeOutput;
use dmyers\orange\interfaces\OutputInterface;

class Output extends OrangeOutput implements OutputInterface
{
    private static OutputInterface $instance;

    // readable for testing
    public $http_response_code = null;
    public $header = [];
    public $setcookie = [];

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function send(bool $exit = false): void
    {
        $this->sendResponseCode()->sendHeaders()->sendCookies();

        // DO NOT ECHO
        // read with get();

        // we never "exit"
    }

    public function sendResponseCode(): self
    {
        $this->alreadySent('Response Code', $this->statusCodeSent);

        $this->http_response_code = $this->statusCode;

        $this->statusCodeSent = true;

        return $this;
    }

    public function sendHeaders(): self
    {
        $this->alreadySent('Headers', $this->headersSent);

        foreach ($this->getHeaders() as $header) {
            $this->header[] = $header;
        }

        $this->headersSent = true;

        return $this;
    }

    public function sendCookies(): self
    {
        $this->alreadySent('Cookies', $this->cookiesSent);

        foreach ($this->cookies as $record) {
            $this->setcookie[] = [
                $record['name'],
                $record['value'],
                $record['setCookieOptions']
            ];
        }

        $this->cookiesSent = true;

        return $this;
    }
}
