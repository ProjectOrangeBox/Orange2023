<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\SecurityInterface;
use orange\framework\exceptions\config\ConfigNotFound;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\filesystem\FileAlreadyExists;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;
use orange\framework\exceptions\security\Security as SecurityException;

/**
 * Overview of Security.php
 *
 * This file defines the Security class in the orange\framework namespace.
 * It is a singleton utility class that provides cryptographic and security-related operations to the framework.
 * It implements the SecurityInterface to ensure a consistent contract for all security features.
 *
 * ⸻
 *
 * 1. Core Purpose
 * 	•	Manage and generate cryptographic keys.
 * 	•	Provide secure encryption and decryption of data.
 * 	•	Create and verify HMAC signatures.
 * 	•	Handle secure password hashing and verification.
 * 	•	Offer input sanitation utilities (filenames, invisible characters).
 *
 * It centralizes all critical cryptographic and security operations in one place.
 *
 * ⸻
 *
 * 2. Key Features
 * 	1.	Key Management (createKeys, getKeyFilePath)
 * 	•	Generates X25519 public/private key pairs for encryption.
 * 	•	Generates an authentication key for HMAC.
 * 	•	Validates configuration, ensures directories are writable, and prevents overwriting existing keys.
 * 	2.	Encryption & Decryption (encrypt, decrypt)
 * 	•	encrypt() → Uses the public key and sodium_crypto_box_seal to encrypt data.
 * 	•	decrypt() → Uses the private key and sodium_crypto_box_seal_open to decrypt.
 * 	•	Handles conversion to/from hexadecimal securely.
 * 	3.	Message Authentication (createSignature, verifySignature)
 * 	•	createSignature() → Generates HMAC signatures using a secret auth key.
 * 	•	verifySignature() → Verifies message signatures with constant-time checks.
 * 	•	Protects against tampering.
 * 	4.	Password Handling (encodePassword, verifyPassword)
 * 	•	Uses Argon2 (via sodium_crypto_pwhash_str) to hash passwords.
 * 	•	Verifies entered passwords against stored hashes.
 * 	•	Protects against brute-force attacks.
 * 	5.	Input Sanitization (removeInvisibleCharacters, cleanFilename)
 * 	•	Removes non-printable characters from input.
 * 	•	Cleans filenames by stripping dangerous characters and encodings (e.g., ../, <, ;, %).
 * 	•	Reduces the risk of injection or traversal attacks.
 *
 * ⸻
 *
 * 3. Security Practices
 * 	•	Uses Libsodium for modern cryptography.
 * 	•	Always overwrites sensitive data in memory (sodium_memzero).
 * 	•	Validates all inputs (hex checks, config paths).
 * 	•	Throws descriptive exceptions for misconfiguration or invalid data.
 *
 * ⸻
 *
 * 4. Big Picture
 *
 * Security.php acts as the security backbone of the Orange framework.
 * It provides consistent, modern, and safe handling of cryptographic operations, authentication,
 * password storage, and input cleaning — ensuring that developers don’t have to reimplement these delicate tasks incorrectly.
 *
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

        // Convert to hex without side-channels
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
        // Check for character(s) representing a hexadecimal digit
        if (!ctype_xdigit($data)) {
            throw new SecurityException('decrypt data argument invalid');
        }

        // Convert from hex without side-channels
        $data = sodium_hex2bin($data);

        // Get the private key
        $key = file_get_contents($this->getKeyFilePath('private'));

        // Anonymous public-key encryption (decrypt)
        $decrypt = sodium_crypto_box_seal_open($data, $key);

        // Overwrite a string with NUL characters
        sodium_memzero($data);
        sodium_memzero($key);

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

        // Convert to hex without side-channels
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
            // Convert from hex without side-channels
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
