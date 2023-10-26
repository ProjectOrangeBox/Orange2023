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
    public $isSent = false;

    public function send(bool $exit = false): void
    {
        $this->sendResponseCode()->sendHeaders()->sendCookies();

        $this->isSent = true;
    }

    public function sendResponseCode(): self
    {
        // 
        $this->http_response_code = $this->statusCode;
        //http_response_code($this->statusCode);

        $this->statusCodeSent = true;

        return $this;
    }

    public function sendHeaders(): self
    {
        if ($this->isSent) {
            throw new OutputException('Output already started.');
        }

        // add our content type
        $this->header('Content-Type', $this->contentType . '; charset=' . $this->charSet);

        // send headers
        foreach ($this->headers as $index => $header) {
            if (!$header['sent']) {
                $this->header[] = $header['key'] . ': ' . $header['value'];
                // header($header['key'] . ': ' . $header['value']);

                // flip send flag
                $this->headers[$index]['sent'] = true;
            }
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
                // setcookie($cookie['name'], $cookie['value'], $cookie['setCookieOptions']);

                $this->cookies[$key]['sent'] = true;
            }
        }

        return $this;
    }
}
