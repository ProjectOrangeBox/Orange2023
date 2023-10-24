<?php

declare(strict_types=1);

namespace peels\console;

interface ConsoleInterface
{
    // output
    public function echo(string $string, bool $linefeed = true, string $stream = 'STDOUT', bool $stop = false): self;
    
    public function primary(string $string, bool $linefeed = true): self;
    public function secondary(string $string, bool $linefeed = true): self;
    public function success(string $string, bool $linefeed = true): self;
    public function danger(string $string, bool $linefeed = true): self;
    public function warning(string $string, bool $linefeed = true): self;
    public function info(string $string, bool $linefeed = true): self;
    public function stop(string $string, bool $linefeed = true): self;
    public function error(string $string, bool $linefeed = true): self;

    public function bell(int $times = 1): self;
    public function line(int $length = null, string $char = '─'): self;
    public function clear(): self;
    public function linefeed(int $times = 1): self;

    // output
    public function table(array $table): self;
    public function list(array $list): self;

    // read
    public function getLine(string $prompt = null): string;
    public function getLineOneOf(string $prompt = null, array $options): string;

    public function get(string $prompt = null): string;
    public function getOneOf(string $prompt = null, array $options): string;

    // test arguments
    public function minimumArguments(int $num, string $error = null): self;
    public function getArgument(int $num, string $error = null): string;
    public function getLastArgument(): string;
    public function getArgumentByOption(string $match, string $error = null): string;
    public function getArgumentExists(string $match): bool;

    // set
    public function verbose(int $level): self;

    // set output filer everything equal or below this value
    public function setVerboseFilter(int $level): self;

    // get
    public function getVerboseLevel(): int;

    // test
    public function ifVerbose(int $level): bool;
}
