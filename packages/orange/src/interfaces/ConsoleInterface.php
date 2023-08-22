<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConsoleInterface
{
    public function echo(string $string, bool $linefeed = true): self;

    public function success(string $string, bool $linefeed = true): self;
    public function info(string $string, bool $linefeed = true): self;
    public function warning(string $string, bool $linefeed = true): self;
    public function error(string $string, bool $linefeed = true): self;
    public function stop(string $string, bool $linefeed = true): void;

    public function bell(int $times = 1): self;
    public function line(int $length = null, string $char = '─'): self;
    public function clear(): self;
    public function linefeed(int $times = 1): self;

    public function table(array $table, array $options = []): self;
    public function list(array $list, array $options = []): self;

    public function getLine(string $prompt = null): string;
    public function getLineOneOf(string $prompt = null, array $options): string;

    public function get(string $prompt = null): string;
    public function getOneOf(string $prompt = null, array $options): string;

    public function minimumArguments(int $num, string $error = null): self;
    public function getArgument(int $num, string $error = null): string;
    public function getLastArgument(): string;
    public function getArgumentByOption(string $match, string $error = null): string;
}
