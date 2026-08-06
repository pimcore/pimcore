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
use Pimcore\Telemetry\Spool\EncryptedBatch;
use Pimcore\Telemetry\Spool\SpooledBatch;
use Pimcore\Telemetry\Spool\TelemetryOutboxService;
use Pimcore\Telemetry\Spool\TelemetrySpoolReaderInterface;
use Pimcore\Tests\Support\Test\TestCase;

class TelemetryOutboxServiceTest extends TestCase
{
    private const PRODUCT_KEY = 'product-key-outbox';

    private const INSTANCE = 'inst-xyz';

    private function reader(?SpooledBatch $batch, array &$acked = [], array &$released = []): TelemetrySpoolReaderInterface
    {
        return new class($batch, $acked, $released) implements TelemetrySpoolReaderInterface {
            /**
             * @param array<int, string> $acked
             * @param array<int, string> $released
             */
            public function __construct(
                private readonly ?SpooledBatch $batch,
                private array &$acked,
                private array &$released,
            ) {
            }

            public function claim(int $limit = 500): ?SpooledBatch
            {
                return $this->batch;
            }

            public function ack(string $nonce): int
            {
                $this->acked[] = $nonce;

                return 1;
            }

            public function release(string $nonce): int
            {
                $this->released[] = $nonce;

                return 1;
            }
        };
    }

    public function testIsReadyRequiresInstanceIdAndProductKey(): void
    {
        $cipher = new EnvelopeCipher();
        $this->assertTrue((new TelemetryOutboxService($this->reader(null), $cipher, self::INSTANCE, self::PRODUCT_KEY))->isReady());
        $this->assertFalse((new TelemetryOutboxService($this->reader(null), $cipher, '', self::PRODUCT_KEY))->isReady());
        $this->assertFalse((new TelemetryOutboxService($this->reader(null), $cipher, self::INSTANCE, ''))->isReady());
    }

    public function testNextBatchEncryptsClaimedEventsForTheRelay(): void
    {
        $cipher = new EnvelopeCipher();
        $events = [
            ['type' => 'capture', 'event' => 'object.created', 'properties' => ['element_type' => 'object'], 'eventUid' => 'u1'],
        ];
        $service = new TelemetryOutboxService($this->reader(new SpooledBatch('nonce-abc', $events)), $cipher, self::INSTANCE, self::PRODUCT_KEY);

        $batch = $service->nextBatch();

        $this->assertInstanceOf(EncryptedBatch::class, $batch);
        $this->assertSame('nonce-abc', $batch->nonce);
        $this->assertSame(self::INSTANCE, $batch->instanceIdentifier);
        $this->assertSame(1, $batch->count);

        $inner = $cipher->decrypt($batch->ciphertext, self::PRODUCT_KEY);
        $this->assertSame(self::INSTANCE, $inner['instanceIdentifier']);
        $this->assertIsInt($inner['ts']);
        $this->assertSame($events, $inner['events']);
    }

    public function testNextBatchReturnsNullWhenSpoolEmpty(): void
    {
        $service = new TelemetryOutboxService($this->reader(null), new EnvelopeCipher(), self::INSTANCE, self::PRODUCT_KEY);

        $this->assertNull($service->nextBatch());
    }

    public function testAckAndReleaseDelegateToTheSpool(): void
    {
        $acked = [];
        $released = [];
        $service = new TelemetryOutboxService(
            $this->reader(new SpooledBatch('n', []), $acked, $released),
            new EnvelopeCipher(),
            self::INSTANCE,
            self::PRODUCT_KEY,
        );

        $service->ack('nonce-1');
        $service->release('nonce-2');

        $this->assertSame(['nonce-1'], $acked);
        $this->assertSame(['nonce-2'], $released);
    }
}
