<?php

declare(strict_types=1);

namespace dmyers\orange\stubs;

use dmyers\orange\Output as OrangeOutput;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\exceptions\Output as OutputException;

class Output extends OrangeOutput implements OutputInterface
{
    // readable for testing
    public $http_response_code = null;
    public $header = [];
    public $setcookie = [];

    public function send(bool $exit = false): void
    {
        $this->sendResponseCode()->sendHeaders()->sendCookies();
    }

    public function sendResponseCode(): self
    {
        $this->http_response_code = $this->statusCode;

        $this->statusCodeSent = true;

        return $this;
    }

    public function sendHeaders(): self
    {
        if ($this->isSent) {
            throw new OutputException('Output already started.');
        }

        foreach ($this->getHeaders() as $header) {
            $this->header[] = $header;
        }

        return $this;
    }

    public function sendCookies(): self
    {
        foreach ($this->cookies as $key => $cookie) {
            if (!$cookie['sent']) {
                $this->setcookie[] = [
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['setCookieOptions']
                ];
    
                $this->cookies[$key]['sent'] = true;
            }
        }

        return $this;
    }
}
