<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface SecurityInterface
{
    public function createKeys(): bool;

    public function encrypt(string $data): string;
    public function decrypt(string $data): string;

    public function createSignature(string $text): string;
    public function verifySignature(string $hmac, string $data): bool;

    public function encodePassword(string $password): string;
    public function verifyPassword(string $hash, string $userEntered): bool;

    public function removeInvisibleCharacters(string $string): string;
    public function cleanFilename(string $filename): string;
}
