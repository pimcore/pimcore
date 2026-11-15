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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\Crypto\EnvelopeCipher;
use Pimcore\Telemetry\Crypto\EnvelopeCipherException;
use Pimcore\Tests\Support\Test\TestCase;

class EnvelopeCipherTest extends TestCase
{
    /**
     * Frozen fixture that pins the wire format: decrypting FIXTURE_CIPHERTEXT with
     * FIXTURE_PRODUCT_KEY must yield the expected envelope. Any drift in HKDF info, gzip, the
     * AES-256-GCM parameters, or the version-byte format breaks this test loudly. If the
     * license-server relay ever grows a parity test, it should reuse these exact literals.
     */
    private const FIXTURE_PRODUCT_KEY = 'poc-fixture-product-key-v1';

    private const FIXTURE_CIPHERTEXT = 'Av8OhJn7UYUEuro2VIRbiRTkpw9urAMMnEQRCKIzp7VuWuOepM5kqXVJxTDPO2Yyh3CqEvo7bF/7rx3TT1T3Jk9+GpG8VKOCWyune+Ww2G1qYdvxYO9DND+JjervfcnCZsSmKryhdK17J9oUBQth087msZQ8IVVCkZlmiLd9GS/jREw6HSzq7MGxUHTwR/DnHmEuOmAb+95EgoXLYiw9X6O6XS8Ui1QrAtOI4SnmwvM=';

    private EnvelopeCipher $cipher;

    protected function setUp(): void
    {
        $this->cipher = new EnvelopeCipher();
    }

    public function testRoundTripPreservesEnvelope(): void
    {
        $envelope = $this->sampleEnvelope();
        $key = 'some-product-key';

        $decrypted = $this->cipher->decrypt($this->cipher->encrypt($envelope, $key), $key);

        $this->assertSame($envelope, $decrypted);
    }

    public function testEncryptionIsNonDeterministic(): void
    {
        $envelope = $this->sampleEnvelope();
        $key = 'some-product-key';

        $this->assertNotSame(
            $this->cipher->encrypt($envelope, $key),
            $this->cipher->encrypt($envelope, $key),
        );
    }

    public function testEncryptProducesVersion2Blob(): void
    {
        $raw = base64_decode($this->cipher->encrypt($this->sampleEnvelope(), 'some-product-key'), true);

        $this->assertSame("\x02", $raw[0]);
    }

    public function testDecryptWithWrongKeyThrows(): void
    {
        $blob = $this->cipher->encrypt($this->sampleEnvelope(), 'the-right-key');

        $this->expectException(EnvelopeCipherException::class);
        $this->cipher->decrypt($blob, 'the-wrong-key');
    }

    public function testTamperedCiphertextThrows(): void
    {
        $raw = base64_decode($this->cipher->encrypt($this->sampleEnvelope(), 'k'), true);
        $raw[strlen($raw) - 1] = $raw[strlen($raw) - 1] === "\x00" ? "\x01" : "\x00";

        $this->expectException(EnvelopeCipherException::class);
        $this->cipher->decrypt(base64_encode($raw), 'k');
    }

    public function testTooShortBlobThrows(): void
    {
        $this->expectException(EnvelopeCipherException::class);
        $this->cipher->decrypt('AAAA', 'k');
    }

    public function testUnsupportedVersionThrows(): void
    {
        $raw = "\x01" . str_repeat("\x00", 24) . str_repeat("\x00", 20);

        $this->expectException(EnvelopeCipherException::class);
        $this->cipher->decrypt(base64_encode($raw), 'k');
    }

    public function testOversizedEnvelopeIsRejected(): void
    {
        // Compresses to a few KB, but would decompress past the 10 MiB cap - a decompression bomb.
        $envelope = [
            'instanceIdentifier' => 'inst-abc',
            'payload' => str_repeat('a', 11 * 1024 * 1024),
        ];

        $blob = $this->cipher->encrypt($envelope, 'k');

        $this->expectException(EnvelopeCipherException::class);
        $this->expectExceptionMessage('size limit');
        $this->cipher->decrypt($blob, 'k');
    }

    public function testEmptyProductKeyThrows(): void
    {
        $this->expectException(EnvelopeCipherException::class);
        $this->cipher->encrypt($this->sampleEnvelope(), '');
    }

    public function testDecryptsFrozenCrossRepoFixture(): void
    {
        $expected = [
            'instanceIdentifier' => 'fixture-inst',
            'ts' => 1720000000,
            'events' => [
                [
                    'type' => 'capture',
                    'event' => 'instance.snapshot',
                    'properties' => ['core.version' => '2026.2'],
                    'eventUid' => 'fixed-uid-1',
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            $this->cipher->decrypt(self::FIXTURE_CIPHERTEXT, self::FIXTURE_PRODUCT_KEY),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleEnvelope(): array
    {
        return [
            'instanceIdentifier' => 'inst-abc',
            'ts' => 1720000000,
            'events' => [
                ['type' => 'capture', 'event' => 'object.created', 'properties' => ['element_type' => 'object'], 'eventUid' => 'u1'],
            ],
        ];
    }
}
