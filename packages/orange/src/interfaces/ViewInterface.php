<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface ViewInterface
{
    public function render(string $view = '', array $data = []): string;
    public function renderString(string $string, array $data = []): string;
    public function change(string $name, mixed $value): self;
}
