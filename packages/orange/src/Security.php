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

/**
 * Class Security
 *
 * Provides cryptographic utilities, including encryption, decryption,
 * HMAC generation and verification, public/private key management,
 * signature verification, and data sanitization.
 *
 * Key Responsibilities:
 * - Generate cryptographic key pairs.
 * - Encrypt and decrypt data securely.
 * - Generate and verify HMAC signatures.
 * - Validate public key signatures.
 * - Sanitize filenames and remove invisible characters.
 *
 * Implements Singleton and SecurityInterface patterns.
 *
 * @package orange\framework
 */
class Security extends Singleton implements SecurityInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * Minimum required length for HMAC keys.
     */
    protected int $hmacMinimumLength;

    /**
     * Hashing algorithm used for HMAC generation (e.g., SHA256).
     */
    protected string $hmacHashingAlgorithm;

    /**
     * Protected constructor to enforce Singleton pattern.
     *
     * Initializes configuration and sets up HMAC-related settings.
     *
     * @param array $config Configuration settings for the Security service.
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config);

        $this->hmacMinimumLength = $this->config['hmac minumum length'] ?? 0;
        $this->hmacHashingAlgorithm = $this->config['hmac hashing algorithm'] ?? '';
    }

    /**
     * Creates a public/private key pair for encryption.
     *
     * Validates file paths, ensures directories are writable,
     * and prevents overwriting existing keys.
     *
     * @return bool True if keys are successfully created.
     * @throws ConfigNotFound If public or private key paths are not defined.
     * @throws DirectoryNotWritable If key directories are not writable.
     * @throws FileAlreadyExists If key files already exist.
     */
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

        // Generate private key pair
        $privateKey = sodium_crypto_box_keypair();

        $success1 = file_put_contents($privateKeyFileLocation, $privateKey);
        $success2 = file_put_contents($publicKeyFileLocation, sodium_crypto_box_publickey($privateKey));

        return ($success1 > 0 && $success2 > 0);
    }

    /**
     * Encrypts data using the public key.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data (base64-encoded).
     */
    public function encrypt(string $data): string
    {
        return base64_encode(sodium_crypto_box_seal($data, $this->getKeyFilePath('public')));
    }

    /**
     * Decrypts data using the private key.
     *
     * @param string $data Encrypted data (base64-encoded).
     * @return string Decrypted data.
     */
    public function decrypt(string $data): string
    {
        return sodium_crypto_box_seal_open(base64_decode($data), $this->getKeyFilePath('private'));
    }

    /**
     * Generates a SHA-1 signature of the public key.
     *
     * @return string The SHA-1 signature of the public key.
     */
    public function publicSig(): string
    {
        return sha1($this->getKeyFilePath('public'), false);
    }

    /**
     * Verifies the SHA-1 signature of the public key.
     *
     * @param string $sig Signature to verify.
     * @return bool True if the signature matches, false otherwise.
     */
    public function verifySig(string $sig): bool
    {
        return $sig === $this->publicSig();
    }

    /**
     * Generates an HMAC for given data.
     *
     * @param string $data Data to hash.
     * @return string The generated HMAC.
     */
    public function hmac(string $data): string
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey());
    }

    /**
     * Verifies an HMAC signature.
     *
     * @param string $data Data with HMAC to verify.
     * @return bool True if the HMAC is valid, false otherwise.
     */
    public function verifyHmac(string $data): bool
    {
        return hash_hmac($this->hmacHashingAlgorithm, $data, $this->getHmacKey()) === $data;
    }

    /**
     * Removes invisible characters from a string.
     *
     * @param string $string Input string.
     * @return string Cleaned string without invisible characters.
     */
    public function removeInvisibleCharacters(string $string): string
    {
        $nonDisplayables = '/[\x00-\x1F\x7F-\xFFFF]+/S';   // 00-31, 127-65535

        do {
            $string = preg_replace($nonDisplayables, '', $string, -1, $count);
        } while ($count);

        return $string;
    }

    /**
     * Sanitizes a filename by removing potentially dangerous characters.
     *
     * @param string $filename Input filename.
     * @return string Cleaned filename.
     */
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

    /**
     * Retrieves the HMAC key from configuration.
     *
     * @return string HMAC key.
     * @throws InvalidValue If the key is missing or too short.
     */
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

    /**
     * Retrieves the contents of a key file (public or private).
     *
     * This method validates the key type (`public` or `private`), ensures the key exists
     * in the configuration, and checks if the file exists before reading its contents.
     *
     * @param string $which Specifies the type of key to retrieve (`public` or `private`).
     * @return string The contents of the requested key file.
     *
     * @throws InvalidValue If the key type is not 'public' or 'private'.
     * @throws ConfigNotFound If the key path is missing from the configuration.
     * @throws FileNotFound If the key file does not exist at the specified path.
     */
    protected function getKeyFilePath(string $which): string
    {
        // Validate that the key type is either 'public' or 'private'.
        if (!in_array($which, ['public', 'private'])) {
            throw new InvalidValue('Unknown key file [' . $which . '].');
        }

        // Build the configuration key (e.g., 'public key' or 'private key').
        $configKey = $which . ' key';

        // Ensure the key path exists in the configuration.
        if (!isset($this->config[$configKey])) {
            throw new ConfigNotFound($configKey);
        }

        // Ensure the key file actually exists at the specified path.
        if (!file_exists($this->config[$configKey])) {
            throw new FileNotFound($this->config[$configKey]);
        }

        // Return the contents of the key file.
        return file_get_contents($this->config[$configKey]);
    }
}
