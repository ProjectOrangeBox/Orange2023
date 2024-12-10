<?php

declare(strict_types=1);

namespace peels\console;

interface ConsoleInterface
{
    // output
    public function echo(int $level, string $string, bool $linefeed = true, string $stream = 'STDOUT', bool $stop = false): self;

    public function primary(int $level, string $string, bool $linefeed = true): self;
    public function secondary(int $level, string $string, bool $linefeed = true): self;
    public function success(int $level, string $string, bool $linefeed = true): self;
    public function danger(int $level, string $string, bool $linefeed = true): self;
    public function warning(int $level, string $string, bool $linefeed = true): self;
    public function info(int $level, string $string, bool $linefeed = true): self;
    public function stop(int $level, string $string, bool $linefeed = true): self;
    public function error(int $level, string $string, bool $linefeed = true): self;

    public function bell(int $times = 1): self;
    public function line(int $level, int $length = null, string $char = '─'): self;
    public function clear(int $level): self;
    public function linefeed(int $level, int $times = 1): self;

    // output
    public function table(int $level, array $table): self;
    public function list(int $level, array $list): self;

    // read
    public function getLine(string $prompt = null): string;
    public function getLineOneOf(string $prompt = null, array $options = []): string;

    public function get(string $prompt = null): string;
    public function getOneOf(string $prompt = null, array $options = []): string;

    // test arguments
    public function minimumArguments(int $num, string $error = null): self;
    public function getArgument(int $num, string $error = null): string;
    public function getLastArgument(): string;
    public function getArgumentByOption(string $match, string $error = null): string;
    public function getArgumentExists(string $match): bool;

    // get / set
    public function verbose(int $level): int;

    // read from command line and optinally set (default)
    public function readVerboseLevel(bool $set = true): int;
}
