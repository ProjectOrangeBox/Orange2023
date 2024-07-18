<?php

declare(strict_types=1);

namespace peels\negotiate;

interface NegotiateInterface
{
    public function media(array $supported, bool $strictMatch = false): string;
    public function charset(array $supported): string;
    public function encoding(array $supported = []): string;
    public function language(array $supported): string;
}
