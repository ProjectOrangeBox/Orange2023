<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface SecurityInterface
{
    public static function createKeys(string $publicKeyFile, string $privateKeyFile, int $bits = 2048, int $type = OPENSSL_KEYTYPE_RSA): bool;
    public static function removeInvisibleCharacters(string $string): string;

    public function publicSig(): string;
    public function verifySig($sig);
    
    public function encrypt(string $data): string;
    public function decrypt(string $data): string;

}
