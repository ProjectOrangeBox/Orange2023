<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\SecurityInterface;
use orange\framework\exceptions\config\ConfigNotFound;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\filesystem\FileAlreadyExists;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;

class Security extends Singleton implements SecurityInterface
{
    use ConfigurationTrait;

    protected int $hmacMinimumLength;
    protected string $hmacHashingAlgorithm;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config);

        $this->hmacMinimumLength = $this->config['hmac minumum length'] ?? 0;
        $this->hmacHashingAlgorithm = $this->config['hmac hashing algorithm'] ?? '';
    }

    public function createKeys(): bool
    {
        if (!isset($this->config['public key'])) {
            throw new ConfigNotFound('private key');
        }

        if (!isset($this->config['private key'])) {
            throw new ConfigNotFound('private key');
        }

        $publicKeyFileLocation = $this->config['public key'];
        $privateKeyFileLocation = $this->config['private key'];

        if (!is_writable(dirname($publicKeyFileLocation))) {
            throw new DirectoryNotWritable(dirname($publicKeyFileLocation));
        }

        if (!is_writable(dirname($privateKeyFileLocation))) {
            throw new DirectoryNotWritable(dirname($privateKeyFileLocation));
        }

        if (file_exists($publicKeyFileLocation)) {
            throw new FileAlreadyExists($publicKeyFileLocation);
        }

        if (file_exists($privateKeyFileLocation)) {
            throw new FileAlreadyExists($privateKeyFileLocation);
        }

        $privateKey = sodium_crypto_box_keypair();

        $success1 = file_put_contents($privateKeyFileLocation, $privateKey);
        $success2 = file_put_contents($publicKeyFileLocation, sodium_crypto_box_publickey($privateKey));

        return ($success1 > 0 && $success2 > 0);
    }

    public function encrypt(string $data): string
    {
        return base64_encode(sodium_crypto_box_seal($data, $this->getKeyFilePath('public')));
    }

    public function decrypt(string $data): string
    {
        return sodium_crypto_box_seal_open(base64_decode($data), $this->getKeyFilePath('private'));
    }

    /**
     * Get the public key signature
     */
    public function publicSig(): string
    {
        return sha1($this->getKeyFilePath('public'), false);
    }

    /**
     * Verify the public key signature
     */
    public function verifySig(string $sig): bool
    {
        return $sig === $this->publicSig();
    }

    public function hmac(string $data): string
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey());
    }

    public function verifyHmac(string $data): bool
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey()) === $data;
    }

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     */
    public function removeInvisibleCharacters(string $string): string
    {
        $nonDisplayables = '/[\x00-\x1F\x7F-\xFFFF]+/S';   // 00-31, 127-65535

        do {
            $string = preg_replace($nonDisplayables, '', $string, -1, $count);
        } while ($count);

        return $string;
    }

    public function cleanFilename(string $filename): string
    {
        $bad = [
            '../',
            '<!--',
            '-->',
            "'",
            '"',
            '&',
            '$',
            '#',
            ';',
            '?',
            '%20',
            '%22',
            '/',
            '*',
            ':',
            '\\',
            '!',
            '%',
            '`',
            '^',
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

        $filename = $this->removeInvisibleCharacters($filename);

        do {
            $old = $filename;
            $filename = str_replace($bad, '', $filename);
        } while ($old !== $filename);

        return $filename;
    }

    /* protected */

    protected function getHmacKey(): string
    {
        if (!isset($this->config['hmac key'])) {
            throw new InvalidValue('hmac key must be passed to security service.');
        }

        if (strlen($this->config['hmac key']) < $this->hmacMinimumLength || $this->hmacMinimumLength === 0) {
            throw new InvalidValue('minimum length for a hmac key is ' . $this->hmacMinimumLength . ' characters.');
        }

        return $this->config['hmac key'];
    }

    protected function getKeyFilePath(string $which): string
    {
        if (!in_array($which, ['public', 'private'])) {
            throw new InvalidValue('Unknown key file [' . $which . '].');
        }

        $configKey = $which . ' key';

        if (!isset($this->config[$configKey])) {
            throw new ConfigNotFound($configKey);
        }

        if (!file_exists($this->config[$configKey])) {
            throw new FileNotFound($this->config[$configKey]);
        }

        return file_get_contents($this->config[$configKey]);
    }
}
