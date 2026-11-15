<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Telemetry\Crypto;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const OPENSSL_RAW_DATA;
use JsonException;
use Throwable;
use function base64_decode;
use function base64_encode;
use function gzdecode;
use function gzencode;
use function hash_hkdf;
use function is_array;
use function json_decode;
use function json_encode;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;
use function strlen;
use function substr;

/**
 * Authenticated, product-key-based encryption for a telemetry envelope.
 *
 * The instance encrypts the batch with a symmetric key derived (HKDF-SHA256) from its **product
 * key** - the value it received from license.pimcore.com at registration. The relay looks the same
 * product key up by the (cleartext) instance identifier and decrypts. Because only the genuine
 * instance and the relay share that key, a batch that decrypts is authentic: this AEAD both
 * protects confidentiality/integrity AND authenticates the sender, so no separate HMAC or bearer
 * token is needed.
 *
 * Wire format (base64 of): [ 1 version byte = 0x02 ][ 12-byte IV ][ 16-byte GCM tag ][ ciphertext ].
 * The payload is gzip-compressed JSON before encryption. The version byte is bound as the AEAD's
 * additional authenticated data, so an algorithm/version downgrade fails authentication, and it
 * lets us rotate the algorithm later without a flag day.
 *
 * This class MUST stay byte-for-byte compatible with its mirror in the license-server relay
 * ({@see \App\Telemetry\Crypto\EnvelopeCipher}); the shared round-trip fixture guards that.
 *
 * @internal
 */
final class EnvelopeCipher
{
    /**
     * Format/algorithm version. v2 = OpenSSL AES-256-GCM over gzip'd JSON.
     */
    private const VERSION = "\x02";

    /**
     * HKDF context string. Domain-separates the telemetry key from any other use of the product key.
     */
    private const HKDF_INFO = 'pimcore-telemetry-aead-v2';

    private const CIPHER = 'aes-256-gcm';

    private const KEY_LEN = 32;

    private const IV_LEN = 12;

    private const TAG_LEN = 16;

    private const GZIP_LEVEL = 6;

    /**
     * Upper bound for the decompressed envelope. Bounds memory on decrypt: without it, a tiny
     * authenticated ciphertext could gzip-expand until PHP exhausts memory. Product keys are
     * distributed to customer instances, so authentication alone does not protect against this.
     */
    private const MAX_PLAINTEXT_BYTES = 10_485_760;

    /**
     * @param array<string, mixed> $envelope
     *
     * @throws EnvelopeCipherException
     */
    public function encrypt(array $envelope, string $productKey): string
    {
        try {
            $json = json_encode($envelope, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $packed = gzencode($json, self::GZIP_LEVEL);

            if ($packed === false) {
                throw new EnvelopeCipherException('Unable to compress telemetry envelope');
            }

            $iv = random_bytes(self::IV_LEN);
            $tag = '';
            $cipher = openssl_encrypt(
                $packed,
                self::CIPHER,
                $this->deriveKey($productKey),
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                self::VERSION,
                self::TAG_LEN,
            );

            if ($cipher === false) {
                throw new EnvelopeCipherException('Unable to encrypt telemetry envelope');
            }

            return base64_encode(self::VERSION . $iv . $tag . $cipher);
        } catch (EnvelopeCipherException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new EnvelopeCipherException('Unable to encrypt telemetry envelope', 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws EnvelopeCipherException
     */
    public function decrypt(string $blob, string $productKey): array
    {
        $raw = base64_decode($blob, true);

        if ($raw === false) {
            throw new EnvelopeCipherException('Telemetry blob is not valid base64');
        }

        // 1 version byte + IV + at least the GCM tag (empty message is still TAG_LEN long)
        if (strlen($raw) < 1 + self::IV_LEN + self::TAG_LEN) {
            throw new EnvelopeCipherException('Telemetry blob is too short');
        }

        if ($raw[0] !== self::VERSION) {
            throw new EnvelopeCipherException('Unsupported telemetry envelope version');
        }

        $iv = substr($raw, 1, self::IV_LEN);
        $tag = substr($raw, 1 + self::IV_LEN, self::TAG_LEN);
        $cipher = substr($raw, 1 + self::IV_LEN + self::TAG_LEN);

        $packed = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $this->deriveKey($productKey),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::VERSION,
        );

        if ($packed === false) {
            // wrong key or tampered ciphertext - GCM tag verification failed
            throw new EnvelopeCipherException('Telemetry envelope failed authentication');
        }

        // The extra byte lets us tell "exactly at the cap" apart from "truncated at the cap".
        $json = gzdecode($packed, self::MAX_PLAINTEXT_BYTES + 1);

        if ($json === false) {
            throw new EnvelopeCipherException('Unable to decompress telemetry envelope');
        }

        if (strlen($json) > self::MAX_PLAINTEXT_BYTES) {
            throw new EnvelopeCipherException('Telemetry envelope exceeds the size limit');
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new EnvelopeCipherException('Telemetry envelope is not valid JSON', 0, $exception);
        }

        if (!is_array($data)) {
            throw new EnvelopeCipherException('Telemetry envelope did not decode to an array');
        }

        return $data;
    }

    /**
     * Derive the symmetric key from the product key. Both sides derive it identically, so the same
     * product key yields the same key - the whole scheme rests on this being deterministic.
     *
     * @throws EnvelopeCipherException
     */
    private function deriveKey(string $productKey): string
    {
        if ($productKey === '') {
            throw new EnvelopeCipherException('Cannot derive a telemetry key from an empty product key');
        }

        return hash_hkdf('sha256', $productKey, self::KEY_LEN, self::HKDF_INFO);
    }
}
