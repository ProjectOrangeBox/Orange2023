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
 * This class provides a suite of cryptographic and security-related utilities,
 * including key pair generation, encryption, decryption, HMAC signature
 * generation and verification, password hashing, and input sanitization.
 *
 * Key Features:
 * - Generate public/private key pairs.
 * - Encrypt and decrypt data securely.
 * - Generate and verify HMAC signatures.
 * - Sanitize filenames and remove invisible characters.
 * - Secure password hashing and verification.
 *
 * Implements Singleton and SecurityInterface patterns to ensure a single
 * instance and a consistent interface across the application.
 *
 * @package orange\framework
 */
class Security extends Singleton implements SecurityInterface
{
    /**
     * Constructor for the Security class.
     *
     * This protected constructor enforces the Singleton pattern by preventing
     * direct instantiation. It initializes the configuration settings.
     *
     * @param array $config Security configuration settings.
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);
        $this->config = $config;
    }

    /**
     * Generates public, private, and authentication keys.
     *
     * Validates file paths, ensures directories are writable, and prevents
     * overwriting existing key files.
     *
     * @return bool Returns true if all keys are successfully created.
     *
     * @throws ConfigNotFound If key paths are missing from the configuration.
     * @throws DirectoryNotWritable If the directories for keys are not writable.
     * @throws FileAlreadyExists If key files already exist.
     */
    public function createKeys(): bool
    {
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

        // Generate an X25519 keypair for use with the sodium_crypto_box API
        $privateKey = sodium_crypto_box_keypair();

        // try to write the private and public keys
        $success1 = file_put_contents($this->config['private key'], $privateKey);
        // Get an X25519 public key from an X25519 keypair
        $success2 = file_put_contents($this->config['public key'], sodium_crypto_box_publickey($privateKey));

        // Overwrite a string with NUL characters
        sodium_memzero($privateKey);

        // Get random bytes for key
        $authKey = sodium_crypto_auth_keygen();

        // write the auth key salt
        $success3 = file_put_contents($this->config['auth key'], $authKey);

        // Overwrite a string with NUL characters
        sodium_memzero($authKey);

        return $success1 > 0 && $success2 > 0 && $success3 > 0;
    }

    /**
     * Encrypts data using the public key.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data in hexadecimal format.
     */
    public function encrypt(string $data): string
    {
        $key = file_get_contents($this->getKeyFilePath('public'));

        // Convert to hex without side-chanels
        $encrypted = sodium_bin2hex(sodium_crypto_box_seal($data, $key));

        // Overwrite a string with NUL characters
        sodium_memzero($key);
        sodium_memzero($data);

        return $encrypted;
    }

    /**
     * Decrypts encrypted data using the private key.
     *
     * @param string $data Encrypted data in hexadecimal format.
     * @return string Decrypted plain text data.
     *
     * @throws SecurityException If the data format is invalid.
     */
    public function decrypt(string $data): string
    {
        $key = file_get_contents($this->getKeyFilePath('private'));

        // Check for character(s) representing a hexadecimal digit
        if (!ctype_xdigit($data)) {
            throw new SecurityException('decrypt data argument invalid');
        }

        // Convert from hex without side-chanels
        $data = sodium_hex2bin($data);

        // Anonymous public-key encryption (decrypt)
        $decrypt = sodium_crypto_box_seal_open($data, $key);

        // Overwrite a string with NUL characters
        sodium_memzero($key);
        sodium_memzero($data);

        return $decrypt;
    }

    /**
     * Generates an HMAC signature for a given message.
     *
     * @param string $message The message to sign.
     * @return string HMAC signature in hexadecimal format.
     */
    public function createSignature(string $message): string
    {
        $key = file_get_contents($this->getKeyFilePath('auth'));

        // Convert to hex without side-chanels
        $token = sodium_bin2hex(sodium_crypto_auth($message, $key));

        // Overwrite a string with NUL characters
        sodium_memzero($key);
        sodium_memzero($message);

        return $token;
    }

    /**
     * Verifies an HMAC signature against a message.
     *
     * @param string $signature HMAC signature to verify.
     * @param string $message Original message.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function verifySignature(string $signature, string $message): bool
    {
        $isValid = false;

        // Check for character(s) representing a hexadecimal digit
        if (ctype_xdigit($signature)) {
            // Convert from hex without side-chanels
            $signature = sodium_hex2bin($signature);

            if (mb_strlen($signature, '8bit') === SODIUM_CRYPTO_AUTH_BYTES) {
                $key = file_get_contents($this->getKeyFilePath('auth'));

                // Secret-key message verification - HMAC SHA-512/256
                $isValid = sodium_crypto_auth_verify($signature, $message, $key);

                // Overwrite a string with NUL characters
                sodium_memzero($key);
            }
        }

        // Overwrite a string with NUL characters
        sodium_memzero($signature);
        sodium_memzero($message);

        return $isValid;
    }

    /**
     * Hashes a password securely using Argon2.
     *
     * @param string $password Plain text password.
     * @return string Hashed password.
     */
    public function encodePassword(string $password): string
    {
        // Get a formatted password hash (for storage)
        $encoded = sodium_crypto_pwhash_str($password, SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);

        // Overwrite a string with NUL characters
        sodium_memzero($password);

        return $encoded;
    }

    /**
     * Verifies a password against its hash.
     *
     * @param string $hash Password hash.
     * @param string $userEntered User-entered password.
     * @return bool True if the password is valid, false otherwise.
     */
    public function verifyPassword(string $hash, string $userEntered): bool
    {
        // Verify a password against a hash
        $isValid = sodium_crypto_pwhash_str_verify($hash, $userEntered);

        // Overwrite a string with NUL characters
        sodium_memzero($hash);
        sodium_memzero($userEntered);

        return $isValid;
    }

    /**
     * Removes invisible characters from a string.
     *
     * @param string $string Input string.
     * @return string Sanitized string.
     */
    public function removeInvisibleCharacters(string $string): string
    {
        $nonDisplayables = '/[\x00-\x1F\x7F-\xFFFF]+/S';   // 00-31, 127-65535

        do {
            // Perform a regular expression search and replace
            $string = preg_replace($nonDisplayables, '', $string, -1, $count);
        } while ($count);

        return $string;
    }

    /**
     * Sanitizes a filename by removing malicious characters.
     *
     * @param string $filename Input filename.
     * @return string Sanitized filename.
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
     * Retrieves the file path of a specified key type.
     *
     * @param string $which Key type: public, private, or auth.
     * @return string Path to the specified key.
     *
     * @throws InvalidValue
     * @throws ConfigNotFound
     * @throws FileNotFound
     */
    protected function getKeyFilePath(string $which): string
    {
        if (!in_array($which, ['public', 'private', 'auth'])) {
            throw new InvalidValue($which . ' is an unknown key file type.');
        }

        $configKey = $which . ' key';

        if (!isset($this->config[$configKey])) {
            throw new ConfigNotFound($configKey);
        }

        if (!file_exists($this->config[$configKey])) {
            throw new FileNotFound($this->config[$configKey]);
        }

        return $this->config[$configKey];
    }
}
