<?php

declare(strict_types=1);

namespace dmyers\cookie;

use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

/**
 * Wrapper for cookies input and output
 */
class Cookie implements CookieInterface
{
    private static ?CookieInterface $instance = null;

    protected InputInterface $input;
    protected OutputInterface $output;

    private function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public static function getInstance(InputInterface $input, OutputInterface $output): self
    {
        if (self::$instance === null) {
            self::$instance = new self($input, $output);
        }

        return self::$instance;
    }

    /* input */
    public function get(string $name, string $default = null)
    {
        return $this->input->cookie($name, $default);
    }

    public function has(string $name): bool
    {
        return $this->input->cookie($name, UNDEFINED) !== UNDEFINED;
    }

    /* output */
    public function set(string $name, string $value = '', int $expire = -1, string $domain = '', string $path = '/', string $prefix = '', ?bool $secure = null, ?bool $httponly = null, ?bool $samesite = null): void
    {
        $this->output->cookie($name, $value, $expire, $domain, $path, $secure, $httponly, $samesite);
    }

    public function remove(string $name, string $domain = null, string $path = null): void
    {
        $this->output->removeCookie($name, $domain, $path);
    }
}
