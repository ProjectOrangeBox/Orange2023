<?php

declare(strict_types=1);

namespace peel\console;

class Console
{
    static array $foregroundColors = [
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

    static array $backgroundColors = [
        'bg_black' => '40',
        'bg_red' => '41',
        'bg_green' => '42',
        'bg_yellow' => '43',
        'bg_blue' => '44',
        'bg_magenta' => '45',
        'bg_cyan' => '46',
        'bg_light_gray' => '47',
    ];

    static array $options = [
        'underline' => '4',
        'blink' => '5',
        'reverse' => '7',
        'hidden' => '8',
    ];

    static $lf = "\n";
    static $errorColor = 'red';
    static $errorChar = '�';

    public static function echo(string $string, bool $linefeed = true): void
    {
        fwrite(\STDOUT, self::formatOutput($string, $linefeed));
    }

    public static function error(string $string, bool $linefeed = true): void
    {
        fwrite(\STDERR, self::formatOutput('<' . self::$errorColor . '><blink>' . self::$errorChar . '<normal> <' . self::$errorColor . '>' . $string, $linefeed));
    }

    protected static function formatOutput(string $string, bool $linefeed = true): string
    {
        $combined = self::$foregroundColors + self::$backgroundColors + self::$options;

        preg_match_all('/<([^>]*)>/i', $string, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $tag = $match[0];

            if (!isset($combined[$match[1]])) {
                self::stop('Could not find tag "' . $match[1] . '"' . chr(10));
            }

            $string = str_replace($tag, "\033[" . $combined[$match[1]] . "m", $string);
        }

        $lf = ($linefeed) ? self::$lf : '';

        return $string . "\033[0m" . $lf;
    }

    public static function stop(string $string, bool $linefeed = true): void
    {
        self::error($string, $linefeed);

        exit(1);
    }

    public static function bell(int $count = 1): void
    {
        fwrite(\STDOUT, str_repeat("\007", $count));
    }

    public static function line(int $length = null, string $char = '─'): void
    {
        $times = ($length) ?? exec('tput cols');

        fwrite(\STDOUT, str_repeat($char, $times) . self::$lf);
    }
}
