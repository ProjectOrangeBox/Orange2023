<?php

declare(strict_types=1);

namespace peels\language;

interface LanguageInterface
{
    public function use(string $lang): void;
    public function has(string $lang): bool;
    public function line(string $tag, string $default = ''): string;
}
