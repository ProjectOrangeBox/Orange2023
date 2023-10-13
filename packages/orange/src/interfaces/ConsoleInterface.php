<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConsoleInterface
{
    // output
    public function echo(string $string, int $level = 1, bool $linefeed = true, string $stream = 'STDOUT'): self;

    public function bell(int $times = 1, int $level = 1): self;
    public function line(int $length = null, string $char = '─', int $level = 1): self;
    public function clear(int $level = 1): self;
    public function linefeed(int $times = 1, int $level = 1): self;

    // output
    public function table(array $table, int $level = 1): self;
    public function list(array $list, int $level = 1): self;

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

    // get
    public function getVerboseLevel(): int;

    // test
    public function ifVerbose(int $level): bool;
}
