<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface SecurityInterface
{
    public static function createKeys(string $publicKeyFileLocation, string $privateKeyFileLocation, int $bits = 2048, int $type = OPENSSL_KEYTYPE_RSA): bool;
    public function publicSig(): string;
    public function verifySig($sig);

    public function encrypt(string $data): string;
    public function decrypt(string $data): string;

    public function hmac(string $data): string;
    public function verifyHmac(string $data): bool;

    public static function removeInvisibleCharacters(string $string): string;
    public static function cleanFilename(string $filename): string;
}
