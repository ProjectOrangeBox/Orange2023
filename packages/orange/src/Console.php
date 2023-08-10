<?php

declare(strict_types=1);

namespace dmyers\orange;

class Console
{
    protected array $foregroundColors = [
        'bold' => '1',
        'dim' => '2',
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
        'normal' => '0;39',
    ];

    protected array $backgroundColors = [
        'bg_black' => '40',
        'bg_red' => '41',
        'bg_green' => '42',
        'bg_yellow' => '43',
        'bg_blue' => '44',
        'bg_magenta' => '45',
        'bg_cyan' => '46',
        'bg_light_gray' => '47',
    ];

    protected array $options = [
        'underline' => '4',
        'blink' => '5',
        'reverse' => '7',
        'hidden' => '8',
    ];

    protected $lf = "\n";
    protected $errorColor = 'red';
    protected $errorChar = '�';

    private static $instance;
    protected array $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function echo(string $string, bool $linefeed = true): void
    {
        fwrite(\STDOUT, self::formatOutput($string, $linefeed));
    }

    public function error(string $string, bool $linefeed = true): void
    {
        fwrite(\STDERR, self::formatOutput('<' .  $this->errorColor . '><blink>' .  $this->errorChar . '<normal> <' .  $this->errorColor . '>' . $string, $linefeed));
    }

    public function stop(string $string, bool $linefeed = true): void
    {
        self::error($string, $linefeed);

        exit(1);
    }

    public function bell(int $count = 1): void
    {
        fwrite(\STDOUT, str_repeat("\007", $count));
    }

    public function line(int $length = null, string $char = '─'): void
    {
        $times = ($length) ?? exec('tput cols');

        fwrite(\STDOUT, str_repeat($char, $times) .  $this->lf);
    }

    protected function formatOutput(string $string, bool $linefeed = true): string
    {
        $combined =  $this->foregroundColors +  $this->backgroundColors +  $this->options;

        preg_match_all('/<([^>]*)>/i', $string, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $tag = $match[0];

            if (!isset($combined[$match[1]])) {
                self::stop('Could not find tag "' . $match[1] . '"' . chr(10));
            }

            $string = str_replace($tag, "\033[" . $combined[$match[1]] . "m", $string);
        }

        $lf = ($linefeed) ?  $this->lf : '';

        return $string . "\033[0m" . $lf;
    }
}
