<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface SecurityInterface
{
    public function createKeys(): bool;

    public function publicSig(): string;
    public function verifySig(string $sig): bool;

    public function encrypt(string $data): string;
    public function decrypt(string $data): string;

    public function hmac(string $data): string;
    public function verifyHmac(string $data): bool;

    public function removeInvisibleCharacters(string $string): string;
    public function cleanFilename(string $filename): string;
}
