<?php

declare(strict_types=1);

namespace peels\cookie;

use peels\cookie\CookieInterface;
use orange\framework\base\Singleton;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\OutputInterface;

/**
 * Wrapper for cookies input and output
 */
class Cookie extends Singleton implements CookieInterface
{
    use ConfigurationTrait;

    protected InputInterface $input;
    protected OutputInterface $output;

    protected function __construct(array $config, InputInterface $input, OutputInterface $output)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->input = $input;
        $this->output = $output;
    }

    /* input */
    public function get(string $name, ?string $default = null): mixed
    {
        return $this->input->cookie($name, $default);
    }

    public function has(string $name): bool
    {
        return $this->input->cookie($name, UNDEFINED) !== UNDEFINED;
    }

    /* output */
    public function set(string $name, ?string $value = null, int $expires = -1, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false, string $sameSite = ''): void
    {
        $this->output->header('Set-Cookie: ' . $this->generateHeader($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite), OutputInterface::REPLACEEXACT);
    }

    public function remove(string $name, string $path = '', string $domain = ''): void
    {
        // now set the cookie to expire
        $this->set($name, null, 0, $path, $domain);
    }

    /* protected */

    protected function generateHeader(string $name, ?string $value, int $expires, string $path, string $domain, bool $secure, bool $httpOnly, string $sameSite): string
    {
        if ($value == null) {
            $value = 'deleted';
            $expires = 0;
        }

        $header = $name . '=' . rawurlencode($value);

        if ($expires != -1) {
            $header .= '; expires=' . gmdate('D, d-M-Y H:i:s T', time() + $expires) . '; Max-Age=' . $expires - time();
        }

        if ($path != '') {
            $header .= '; path=' . $path;
        } elseif ($this->config['path'] != '') {
            $header .= '; path=' . $this->config['path'];
        }

        if ($domain != '') {
            $header .= '; domain=' . $domain;
        } elseif ($this->config['domain'] != '') {
            $header .= '; domain=' . $this->config['domain'];
        }

        if ($secure || $this->config['secure']) {
            $header .= '; secure';
        }

        if ($httpOnly || $this->config['httponly']) {
            $header .= '; HttpOnly';
        }

        if ($sameSite != '') {
            $header .= '; SameSite=' . $sameSite;
        } elseif ($this->config['samesite'] != '') {
            $header .= '; SameSite=' . $this->config['samesite'];
        }

        return $header;
    }
}
