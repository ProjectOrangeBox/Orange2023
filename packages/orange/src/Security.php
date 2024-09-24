<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\SecurityInterface;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;
use orange\framework\exceptions\filesystem\FileAlreadyExists;
use orange\framework\exceptions\security\Security as ExceptionsSecurity;

class Security extends Singleton implements SecurityInterface
{
    protected array $config;

    protected int $hmacMinimumLength;
    protected string $hmacHashingAlgorithm;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = $config;

        // verify both keys exists in config and in the file system
        $this->getKeyFile('public');
        $this->getKeyFile('private');

        $this->hmacMinimumLength = $config['hmac minimum length'] ?? 32;
        $this->hmacHashingAlgorithm = $config['hmac hashing algorithm'] ?? 'sha256';
    }

    /**
     * Call Statically to CREATE public and private keys
     */
    public static function createKeys(string $publicKeyFileLocation, string $privateKeyFileLocation, int $bits = 2048, int $type = OPENSSL_KEYTYPE_RSA): bool
    {
        $success = false;

        if (!is_writable(dirname($publicKeyFileLocation))) {
            throw new DirectoryNotWritable('Folder [' . dirname($publicKeyFileLocation) . '] is not writable.');
        }

        if (!is_writable(dirname($privateKeyFileLocation))) {
            throw new DirectoryNotWritable('Folder [' . dirname($privateKeyFileLocation) . '] is not writable.');
        }

        if (file_exists($publicKeyFileLocation)) {
            throw new FileAlreadyExists($publicKeyFileLocation);
        }

        if (file_exists($privateKeyFileLocation)) {
            throw new FileAlreadyExists($privateKeyFileLocation);
        }

        if (!in_array($type, [OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_EC])) {
            throw new InvalidValue('Unknown Private Key Type [' . $type . '].');
        }

        $privateKey = openssl_pkey_new([
            'private_key_bits' => $bits,
            'private_key_type' => $type,
        ]);

        if (openssl_pkey_export_to_file($privateKey, $privateKeyFileLocation)) {
            $publicKey = openssl_pkey_get_details($privateKey);

            $success = file_put_contents($publicKeyFileLocation, $publicKey['key']) > 0;
        }

        return $success;
    }

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     */
    public static function removeInvisibleCharacters(string $string): string
    {
        $nonDisplayables = '/[\x00-\x1F\x7F-\xFFFF]+/S';   // 00-31, 127-65535

        do {
            $string = preg_replace($nonDisplayables, '', $string, -1, $count);
        } while ($count);

        return $string;
    }

    public static function cleanFilename(string $filename): string
    {
        $bad = [
            '../', '<!--', '-->',
            "'", '"', '&', '$', '#',
            ';', '?', '%20', '%22',
            '/', '*', ':', '\\',
            '!', '%', '`', '^',
            '%3c',        // <
            '%253c',      // <
            '%3e',        // >
            '%0e',        // >
            '%28',        // (
            '%29',        // )
            '%2528',      // (
            '%26',        // &
            '%24',        // $
            '%3f',        // ?
            '%3b',        // ;
            '%3d'         // =
        ];

        $filename = self::removeInvisibleCharacters($filename);

        do {
            $old = $filename;
            $filename = str_replace($bad, '', $filename);
        } while ($old !== $filename);

        return $filename;
    }

    /**
     * Get the public key signature
     */
    public function publicSig(): string
    {
        return md5_file($this->getKeyFile('public'));
    }

    /**
     * Verify the public key signature
     */
    public function verifySig($sig)
    {
        return $sig === $this->publicSig();
    }

    public function encrypt(string $data): string
    {
        $output = '';

        if (!$key = openssl_pkey_get_public('file://' . $this->getKeyFile('public'))) {
            throw new ExceptionsSecurity('Could not get public key');
        }

        $details = openssl_pkey_get_details($key);

        $length = (int)ceil($details['bits'] / 8) - 11;

        while ($data) {
            $chunk = substr($data, 0, $length);
            $data = substr($data, $length);
            $encrypted = '';

            if (!openssl_public_encrypt($chunk, $encrypted, $key, 1)) {
                throw new ExceptionsSecurity('Failed to encrypt data');
            }

            $output .= $encrypted;
        }

        return bin2hex($output);
    }

    public function decrypt(string $data): string
    {
        $output = '';

        if (!$key = openssl_pkey_get_private('file://' . $this->getKeyFile('private'))) {
            throw new ExceptionsSecurity('Could not get private key');
        }

        $details = openssl_pkey_get_details($key);

        $length = (int)ceil($details['bits'] / 8);

        $data = hex2bin($data);

        while ($data) {
            $chunk = substr($data, 0, $length);
            $data = substr($data, $length);
            $decrypted = '';

            if (!openssl_private_decrypt($chunk, $decrypted, $key)) {
                throw new ExceptionsSecurity('Failed to decrypt data');
            }

            $output .= $decrypted;
        }

        return $output;
    }

    public function hmac(string $data): string
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey());
    }

    public function verifyHmac(string $data): bool
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey()) === $data;
    }

    protected function getHmacKey(): string
    {
        if (!isset($this->config['hmac key'])) {
            throw new InvalidValue('hmac key must be passed to security service.');
        }

        if (strlen($this->config['hmac key']) < $this->hmacMinimumLength) {
            throw new InvalidValue('minimum length for a hmac key is ' . $this->hmacMinimumLength . ' characters.');
        }

        return $this->config['hmac key'];
    }

    protected function getKeyFile(string $which): string
    {
        if (!in_array($which, ['public', 'private'])) {
            throw new InvalidValue('Unknown key file [' . $which . '].');
        }

        $file = $this->config['ssl ' . $which . ' key'];

        if (!file_exists($file)) {
            throw new FileNotFound('Count not locate [' . $which . '] key file.');
        }

        return $file;
    }
}
