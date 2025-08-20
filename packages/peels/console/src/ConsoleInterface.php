<?php

declare(strict_types=1);

namespace peels\console;

interface ConsoleInterface
{
    // output levels
    public const INFO = 1;
    public const NOTICE = 2;
    public const WARNING = 4;
    public const ERROR = 8;
    public const CRITICAL = 16;
    public const ALERT = 32;
    public const EMERGENCY = 64;
    public const DEBUG = 128;

    public const NONE = 0;
    public const EVERYTHING = 65535;
    public const ALWAYS = 0;

    // output
    public function echo(string $string, int $level = self::INFO, bool $linefeed = true, string $stream = \STDOUT, bool $stop = false): self;

    public function primary(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function secondary(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function success(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function danger(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function warning(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function info(string $string, int $level = self::INFO, bool $linefeed = true): self;
    public function stop(string $string, int $level = self::ALWAYS, bool $linefeed = true): self;
    public function error(string $string, int $level = self::ALWAYS, bool $linefeed = true): self;

    public function bell(int $times = 1, int $level = self::INFO): self;
    public function line(?int $length = null, string $char = '─', int $level = self::INFO): self;
    public function clear(int $level = self::INFO): self;
    public function linefeed(int $times = 1, int $level = self::INFO): self;

    // output
    public function table(array $table, int $level = self::INFO): self;
    public function list(array $list, int $level = self::INFO): self;

    // read
    public function getLine(?string $prompt = null): string;
    public function getLineOneOf(?string $prompt = null, array $options = []): string;

    public function get(?string $prompt = null): string;
    public function getOneOf(?string $prompt = null, array $options = []): string;

    // test arguments
    public function minimumArguments(int $num, ?string $error = null): self;
    public function getArgument(int $num, ?string $error = null): string;
    public function getLastArgument(): string;
    public function getArgumentByOption(string $match, ?string $error = null): string;
    public function getArgumentExists(string $match): bool;

    // get / set
    public function verbose(?int $level = null): int;

    // read from command line and optinally set (default)
    public function detectVerboseLevel(bool $set = true, ?string $char = null): int;
}
