<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\OutputInterface;
use orange\framework\exceptions\output\Output as OutputException;

/**
 * Class Output
 *
 * Handles HTTP output operations, including response codes, headers, 
 * content types, character sets, and output buffering. Implements 
 * Singleton design pattern and OutputInterface.
 *
 * This class ensures proper HTTP response management, including:
 * - Setting headers
 * - Managing output buffers
 * - Handling redirects
 * - Enforcing HTTPS
 * - Configuring content types and charsets
 */
class Output extends Singleton implements OutputInterface
{
    use ConfigurationTrait;

    /** @var string $output Stores the output content to be sent to the client */
    protected string $output = '';

    /** @var array $headers Stores HTTP headers to be sent */
    protected array $headers = [];

    /** @var int $responseCode The HTTP response status code */
    protected int $responseCode = 200;

    /** @var array $responseCodesInternalStringKeys Maps internal string keys to HTTP status codes */
    protected array $responseCodesInternalStringKeys = [];

    /** @var string $contentType The Content-Type of the HTTP response */
    protected string $contentType = '';

    /** @var string $charSet The character set of the HTTP response */
    protected string $charSet = '';

    /** @var array $mimes MIME type mappings for content types */
    protected array $mimes = [];

    /** @var InputInterface $input Input interface for managing request details */
    protected InputInterface $input;

    /**
     * Constructor is protected to enforce Singleton pattern.
     * Use Singleton::getInstance() to obtain an instance.
     * 
     * @param array $config Configuration array.
     * @param InputInterface $input Input interface instance.
     */
    protected function __construct(array $config, InputInterface $input)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config);
        $this->input = $input;

        if ($this->config['force https']) {
            $this->forceHttps();
        }

        $this->output = '';
        $this->headers = [];
        $this->responseCodesInternalStringKeys = array_change_key_case(array_flip($this->config['status codes']), CASE_LOWER);
        $this->mimes = $this->config['mimes'] ?? [];

        $this->responseCode($this->responseCode);
        $this->contentType($this->config['contentType']);
        $this->charSet($this->config['charSet']);
    }

    /**
     * Redirects the client to a specified URL.
     *
     * @param string $url Target URL for redirection.
     * @param int $responseCode HTTP status code for the redirection.
     * @param bool $exit Whether to terminate script execution after redirection.
     */
    public function redirect(string $url, int $responseCode = 0, bool $exit = true): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $url . ' ' . $responseCode . ' ' . $exit);

        $responseCode = ($responseCode == 0) ? $this->config['default redirect code'] : $responseCode;

        $this->flushAll()
            ->header('Location: ' . $url, self::REPLACEALL)
            ->responseCode($responseCode)
            ->send($exit);
    }

    /**
     * Enforces HTTPS protocol if the request is not already secure.
     */
    public function forceHttps(): void
    {
        logMsg('INFO', __METHOD__);

        if (!$this->input->isHttpsRequest()) {
            $this->redirect('https://' . $this->input->server('http_host') . $this->input->server('request_uri', $this->config['force http response code']));
        }
    }

    /**
     * Flushes all headers and content.
     * 
     * @return self
     */
    public function flushAll(): self
    {
        logMsg('INFO', __METHOD__);
        return $this->flushHeaders()->flush();
    }

    /**
     * Sends the output content and headers to the client.
     * 
     * @param bool|int $exit Whether to exit after sending the output.
     */
    public function send(bool|int $exit = false): void
    {
        logMsg('INFO', __METHOD__);

        if (!$this->input->isCliRequest()) {
            foreach ($this->headers as $header) {
                $this->phpHeader($header);
            }
        }

        $this->phpEcho($this->output);

        if ($exit) {
            $exitCode = ($exit === true) ? 0 : $exit;
            $this->phpExit($exitCode);
        }
    }

    /**
     * Clears the output content.
     * 
     * @return self
     */
    public function flush(): self
    {
        logMsg('INFO', __METHOD__);
        $this->output = '';
        return $this;
    }

    /**
     * Writes content to the output buffer.
     * 
     * @param string $string Content to write.
     * @param bool $append Whether to append or overwrite the buffer.
     * @return self
     */
    public function write(string $string, bool $append = true): self
    {
        logMsg('INFO', __METHOD__);

        $this->output = $append ? $this->output . $string : $string;

        return $this;
    }

    /**
     * Gets the current output buffer.
     * 
     * @return string
     */
    public function get(): string
    {
        return $this->output;
    }

    /**
     * Sets the Content-Type header.
     * 
     * @param string $type MIME type.
     * @param string $fallback Fallback MIME type.
     * @return self
     */
    public function contentType(string $type, string $fallback = ''): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $type);

        // if they send in the shorthand content type convert it to a proper content type
        if (isset($this->mimes[$type])) {
            $contentType = $this->mimes[$type];
        } elseif (isset($this->mimes[$fallback])) {
            $contentType = $this->mimes[$fallback];
        } elseif (in_array($type, $this->mimes)) {
            $contentType = $type;
        } elseif (in_array($fallback, $this->mimes)) {
            $contentType = $fallback;
        } else {
            throw new OutputException('Unknown contentType(s) ' . $type . '/' . ($fallback ?? ''));
        }

        logMsg('INFO', __METHOD__ . ' ' . $contentType);

        $this->contentType = $contentType;
        $this->header($this->getContentTypeHeader($this->contentType, $this->charSet), self::REPLACEALL);

        return $this;
    }

    /**
     * Retrieves the current content type.
     *
     * @return string
     */
    public function getContentType(): string
    {
        logMsg('INFO', __METHOD__);

        return $this->contentType;
    }

    /**
     * Sets the character set.
     * 
     * @param string $charSet Character set to use.
     * @return self
     */
    public function charSet(string $charSet): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $charSet);

        $this->charSet = $charSet;

        $this->header($this->getContentTypeHeader($this->contentType, $this->charSet), self::REPLACEALL);

        return $this;
    }

    /**
     * Gets the current character set.
     * 
     * @return string
     */
    public function getCharSet(): string
    {
        logMsg('INFO', __METHOD__);

        return $this->charSet;
    }

    /**
     * Sets an HTTP header for the response.
     *
     * This method supports flexible header management, including replacing or prepending headers.
     *
     * @param string $value The header string to be sent (e.g., 'Content-Type: text/html').
     * @param int $replace Flag indicating whether to replace existing headers with the same prefix.
     *                     - Use `self::NO` to prevent replacement.
     *                     - Use `self::REPLACEALL` to replace all matching headers.
     * @param bool $prepend Whether to prepend the header to the list instead of appending.
     * @return self
     */
    public function header(string $value, int $replace = self::NO, bool $prepend = false): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $value . ' ' . $replace . ' ' . $prepend);

        if ($replace != self::NO) {
            $splitOn = ($replace == self::REPLACEALL) ? '/(:| )/' : '/(;|=|,)/';
            $prefix = strtolower(preg_split($splitOn, $value)[0]);
            $prefixLength = strlen($prefix);

            foreach ($this->headers as $index => $headerValue) {
                if (substr(strtolower($headerValue), 0, $prefixLength) == $prefix) {
                    unset($this->headers[$index]);
                }
            }
        }

        if ($prepend) {
            array_unshift($this->headers, $value);
        } else {
            $this->headers[] = $value;
        }

        return $this;
    }

    /**
     * Retrieves all currently set HTTP headers.
     *
     * This method returns all headers prepared for the response.
     *
     * @return array An array of HTTP headers.
     */
    public function getHeaders(): array
    {
        logMsg('DEBUG', __METHOD__);

        $headers = array_values($this->headers);

        logMsg('INFO', '', $headers);

        return $headers;
    }

    /**
     * Clears all currently set HTTP headers.
     *
     * This method resets the headers array, ensuring no previously set headers are sent.
     *
     * @return self
     */
    public function flushHeaders(): self
    {
        logMsg('INFO', __METHOD__);

        $this->headers = [];

        return $this;
    }

    /**
     * Sets the HTTP response code.
     *
     * Allows setting a response code either by integer value or by a string key mapped internally.
     *
     * @param int|string $code The HTTP status code (e.g., 200, 404) or its string representation.
     * @return self
     * @throws OutputException If the status code is unknown or invalid.
     */
    public function responseCode(int|string $code): self
    {
        logMsg('DEBUG', __METHOD__, ['code' => $code]);

        if (is_string($code)) {
            $code = strtolower($code);

            if (!array_key_exists($code, $this->responseCodesInternalStringKeys)) {
                throw new OutputException('Unknown HTTP Status Code ' . $code);
            }

            $code = $this->responseCodesInternalStringKeys[$code];
        }

        // test the integer
        if (!isset($this->config['status codes'][$code])) {
            throw new OutputException('Unknown HTTP Status Code ' . (string)$code);
        }

        // code is valid integer
        $this->responseCode = $code;

        $this->header($this->getResponseHeader($this->responseCode), self::REPLACEALL, true);

        return $this;
    }

    /**
     * Retrieves the currently set HTTP response code.
     *
     * @return int The HTTP response code.
     */
    public function getResponseCode(): int
    {
        logMsg('INFO', __METHOD__);

        return $this->responseCode;
    }

    /**
     * Generates a Content-Type header string.
     *
     * Combines the content type and charset into a valid HTTP header string.
     *
     * @param string $contentType The MIME type for the content (e.g., 'text/html').
     * @param string $charSet The character set (e.g., 'UTF-8').
     * @return string The complete Content-Type header string.
     */
    protected function getContentTypeHeader(string $contentType, string $charSet): string
    {
        logMsg('DEBUG', __METHOD__, ['contentType' => $contentType, 'charSet' => $charSet]);

        return 'Content-Type: ' . $contentType . '; charset=' . strtoupper($charSet);
    }

    /**
     * Generates an HTTP response status header string.
     *
     * Combines the HTTP protocol version, response code, and status message.
     *
     * @param int $responseCode The HTTP response status code (e.g., 200, 404).
     * @return string The full HTTP response header.
     */
    protected function getResponseHeader(int $responseCode): string
    {
        logMsg('DEBUG', __METHOD__, ['responseCode' => $responseCode]);

        return $this->input->server('server_protocol', 'HTTP/1.0') . ' ' . $responseCode . ' ' . $this->config['status codes'][$responseCode];
    }

    /**
     * Outputs a string to the client.
     *
     * This method directly echoes the provided string, making it suitable for unit testing overrides.
     *
     * @param string $string The string to output.
     */
    protected function phpEcho(string $string): void
    {
        echo $string;
    }

    /**
     * Terminates script execution with an optional status code.
     *
     * Useful for controlling script termination during testing.
     *
     * @param int $status The exit status code (default is 0).
     */
    protected function phpExit(int $status = 0): void
    {
        exit($status);
    }

    /**
     * Sends an HTTP header.
     *
     * This method serves as a wrapper for PHP's native `header()` function, 
     * allowing easier testing and overriding in unit tests.
     *
     * @param string $header The header string to send.
     * @param bool $replace Whether to replace a previous header with the same name.
     */
    protected function phpHeader(string $header, bool $replace = false): void
    {
        header($header, $replace);
    }
}
