<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\security\Security as SecurityException;
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

        $this->config = $config;
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
        // checks
        foreach (['public key', 'private key', 'auth key'] as $key) {
            if (!isset($this->config[$key])) {
                throw new ConfigNotFound($key);
            }
            if (!is_writable(dirname($this->config[$key]))) {
                throw new DirectoryNotWritable(dirname($this->config[$key]));
            }
            if (file_exists($this->config[$key])) {
                throw new FileAlreadyExists($this->config[$key]);
            }
        }

        // Generate private key pair
        $privateKey = sodium_crypto_box_keypair();

        $success1 = file_put_contents($this->config['private key'], $privateKey);
        $success2 = file_put_contents($this->config['public key'], sodium_crypto_box_publickey($privateKey));

        // clean up
        sodium_memzero($privateKey);

        $authKey = sodium_crypto_auth_keygen();

        $success3 = file_put_contents($this->config['auth key'], $authKey);

        // clean up
        sodium_memzero($authKey);

        return ($success1 > 0 && $success2 > 0 && $success3 > 0);
    }

    /**
     * Encrypts data using the public key.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data (base64-encoded).
     */
    public function encrypt(string $data): string
    {
        $key = file_get_contents($this->getKeyFilePath('public'));

        $encrypted = sodium_bin2hex(sodium_crypto_box_seal($data, $key));

        // make sure we clean up
        sodium_memzero($key);
        sodium_memzero($data);

        return $encrypted;
    }

    /**
     * Decrypts data using the private key.
     *
     * @param string $data Encrypted data (base64-encoded).
     * @return string Decrypted data.
     */
    public function decrypt(string $data): string
    {
        $key = file_get_contents($this->getKeyFilePath('private'));
        
        if (!ctype_xdigit($data)) {
            throw new SecurityException('decrypt data argument invalid');
        }

        $data = sodium_hex2bin($data);

        $decrypt = sodium_crypto_box_seal_open($data, $key);

        // make sure we clean up
        sodium_memzero($key);
        sodium_memzero($data);

        return $decrypt;
    }

    /**
     * Generates a SHA-1 signature of the public key.
     *
     * @return string The SHA-1 signature of the public key.
     */
    public function publicSig(): string
    {
        $key = file_get_contents($this->getKeyFilePath('public'));

        $sig = sha1($key, false);

        // make sure we clean up
        sodium_memzero($key);

        return $sig;
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
     * @param string $message Message to be hashed.
     * @return string The generated HMAC signature.
     */
    public function hmac(string $message): string
    {
        $key = file_get_contents($this->getKeyFilePath('auth'));

        $signature = sodium_bin2hex(sodium_crypto_auth($message, $key));

        // make sure we clean up
        sodium_memzero($key);
        sodium_memzero($message);

        return $signature;
    }

    /**
     * Verifies an HMAC signature.
     *
     * @param string $signature signature you are testing the text against
     * @param string $message Message you want to verify against signature
     * @return bool True if the HMAC is valid, false otherwise.
     */
    public function verifyHmac(string $signature, string $message): bool
    {
        $isValid = false;

        if (ctype_xdigit($signature)) {
            $signature = sodium_hex2bin($signature);

            if (mb_strlen($signature, '8bit') === SODIUM_CRYPTO_AUTH_BYTES) {
                $key = file_get_contents($this->getKeyFilePath('auth'));

                $isValid = sodium_crypto_auth_verify($signature, $message, $key);

                // make sure we clean up
                sodium_memzero($key);
            }
        }

        sodium_memzero($signature);
        sodium_memzero($message);

        return $isValid;
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
     * Retrieves the path of a key files (public, private or auth).
     *
     * This method validates the key type (`public`, `private` or `auth`), ensures the key exists
     * in the configuration, and checks if the file exists before reading its file path.
     * 
     * These are only necessary if you need the key
     * These are not all required when you create the instance
     *
     * @param string $which Specifies the type of key to retrieve (`public` or `private`).
     * @return string The path of the requested key file.
     *
     * @throws InvalidValue If the key type is not 'public' or 'private'.
     * @throws ConfigNotFound If the key path is missing from the configuration.
     * @throws FileNotFound If the key file does not exist at the specified path.
     */
    protected function getKeyFilePath(string $which): string
    {
        // Validate that the key type is either 'public' or 'private'.
        if (!in_array($which, ['public', 'private', 'auth'])) {
            throw new InvalidValue($which . ' is an unknown key file type.');
        }

        // Build the configuration key (e.g., 'public key', 'private key' or 'auth key').
        $configKey = $which . ' key';

        // Ensure the key path exists in the configuration.
        if (!isset($this->config[$configKey])) {
            throw new ConfigNotFound($configKey);
        }

        // Ensure the key file actually exists at the specified path.
        if (!file_exists($this->config[$configKey])) {
            throw new FileNotFound($this->config[$configKey]);
        }

        // return only the path
        return $this->config[$configKey];
    }
}
