<?php

declare(strict_types=1);

namespace peels\console;

interface ConsoleInterface
{
    // output levels
    public const ALL = 0;
    public const BASIC = 1;
    public const DETAILED = 2;
    public const DEBUG = 3;

    // streams
    public const OUTPUT = 'STDOUT';
    public const ERRORS = 'STDERR';

    // output
    public function echo(string $string, int $level = self::BASIC, bool $linefeed = true, string $stream = self::OUTPUT, bool $stop = false): self;

    public function primary(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function secondary(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function success(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function danger(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function warning(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function info(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function stop(string $string, int $level = self::BASIC, bool $linefeed = true): self;
    public function error(string $string, int $level = self::BASIC, bool $linefeed = true): self;

    public function bell(int $times = 1, int $level = self::BASIC): self;
    public function line(?int $length = null, string $char = '─', int $level = self::BASIC): self;
    public function clear(int $level = self::BASIC): self;
    public function linefeed(int $times = 1, int $level = self::BASIC): self;

    // output
    public function table(array $table, int $level = self::BASIC): self;
    public function list(array $list, int $level = self::BASIC): self;

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
    public function verbose(int $level): int;

    // read from command line and optinally set (default)
    public function readVerboseLevel(bool $set = true): int;
}
