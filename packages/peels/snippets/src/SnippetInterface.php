<?php

declare(strict_types=1);

namespace peels\snippets;

interface SnippetInterface
{
    public function __get(string $name): mixed;
    public function get(string $key, string $default = ''): string;
}
