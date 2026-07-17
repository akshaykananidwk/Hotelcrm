<?php
namespace App\Core;

/**
 * Authenticated symmetric encryption (AES-256-GCM) for data at rest such as
 * OTA / gateway credentials. The key is derived from the application key in
 * config so ciphertext is portable within an installation but useless without
 * that key. Output is base64 of: nonce(12) . tag(16) . ciphertext.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'enc:v1:';

    private static function key(): string
    {
        $appKey = (string) App::config('app.key', 'insecure-default-key');
        // 32-byte key regardless of app.key length.
        return hash('sha256', 'hotelcrm|' . $appKey, true);
    }

    public static function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ct === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        return self::PREFIX . base64_encode($nonce . $tag . $ct);
    }

    public static function decrypt(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value; // tolerate legacy plaintext
        }
        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 28) {
            throw new \RuntimeException('Malformed ciphertext.');
        }
        $nonce = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct = substr($raw, 28);
        $pt = openssl_decrypt($ct, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($pt === false) {
            throw new \RuntimeException('Decryption failed (wrong key or tampered data).');
        }
        return $pt;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /** Encrypt an array as a JSON blob. */
    public static function encryptArray(array $data): string
    {
        return self::encrypt(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /** Decrypt a JSON blob back to an array (returns [] on empty). */
    public static function decryptArray(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $json = self::decrypt($value);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
