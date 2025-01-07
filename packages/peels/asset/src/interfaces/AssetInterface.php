<?php

declare(strict_types=1);

namespace peels\asset\Interfaces;

use peels\asset\Priority;

interface AssetInterface
{
    public function __call(string $name, array $arguments): mixed;
    public function has(string $name): bool;
    public function get(string $name): string;
    public function linkHTML(string $file): string;
    public function scriptHTML(string $file): string;
    public function elementHTML(string $element, array $attributes, string $content = '', array $data = null): string;
    public function scriptFile($file = '', int $priority = Priority::NORMAL): self;
    public function scriptFiles(array $array): self;
    public function linkFile($file = '', int $priority = Priority::NORMAL): self;
    public function linkFiles(array $array): self;
    public function javascriptVariable(string $key, $value, bool $raw = false, int $priority = Priority::NORMAL): self;
    public function javascriptVariables(array $array): self;
    public function bodyClass(string|array $class, int $priority = Priority::NORMAL): self;
}
