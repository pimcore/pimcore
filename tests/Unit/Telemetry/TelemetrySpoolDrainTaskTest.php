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

use Pimcore\Maintenance\Tasks\TelemetrySpoolDrainTask;
use Pimcore\Telemetry\Relay\RelayClientInterface;
use Pimcore\Telemetry\Spool\EncryptedBatch;
use Pimcore\Telemetry\Spool\TelemetryOutboxInterface;
use Pimcore\Tests\Support\Test\TestCase;

class TelemetrySpoolDrainTaskTest extends TestCase
{
    public function testDrainsAllBatchesOnSuccessAndAcksEach(): void
    {
        $outbox = $this->outbox([$this->batch('n1', 'c1'), $this->batch('n2', 'c2')]);
        $relay = $this->relay();

        (new TelemetrySpoolDrainTask($outbox, $relay))->execute();

        $this->assertSame(['n1', 'n2'], $outbox->acked);
        $this->assertSame([], $outbox->released);
        $this->assertCount(2, $relay->sent);
    }

    public function testReleasesFailedBatchAndStops(): void
    {
        $outbox = $this->outbox([$this->batch('n1', 'c1'), $this->batch('n2', 'FAIL'), $this->batch('n3', 'c3')]);
        $relay = $this->relay('FAIL');

        (new TelemetrySpoolDrainTask($outbox, $relay))->execute();

        $this->assertSame(['n1'], $outbox->acked, 'only the first batch is acked');
        $this->assertSame(['n2'], $outbox->released, 'the failed batch is released');
        $this->assertCount(2, $relay->sent, 'drain stops after the failure and never reaches n3');
    }

    public function testNoOpWhenOutboxNotReadyOrRelayNotConfigured(): void
    {
        $notReady = $this->outbox([$this->batch('n1', 'c1')], ready: false);
        $relay = $this->relay();
        (new TelemetrySpoolDrainTask($notReady, $relay))->execute();
        $this->assertCount(0, $relay->sent, 'a not-ready outbox must not send anything');
        $this->assertSame([], $notReady->acked);

        $outbox = $this->outbox([$this->batch('n1', 'c1')]);
        $unconfigured = $this->relay(configured: false);
        (new TelemetrySpoolDrainTask($outbox, $unconfigured))->execute();
        $this->assertSame([], $outbox->acked);
    }

    private function batch(string $nonce, string $ciphertext): EncryptedBatch
    {
        return new EncryptedBatch($nonce, 'inst', $ciphertext, 1);
    }

    /**
     * @param list<EncryptedBatch> $batches
     */
    private function outbox(array $batches, bool $ready = true): TelemetryOutboxInterface
    {
        return new class($batches, $ready) implements TelemetryOutboxInterface {
            /** @var array<int, string> */
            public array $acked = [];

            /** @var array<int, string> */
            public array $released = [];

            /** @param list<EncryptedBatch> $batches */
            public function __construct(private array $batches, private readonly bool $ready)
            {
            }

            public function isReady(): bool
            {
                return $this->ready;
            }

            public function nextBatch(): ?EncryptedBatch
            {
                return array_shift($this->batches);
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

    private function relay(?string $failOnCiphertext = null, bool $configured = true): RelayClientInterface
    {
        return new class($failOnCiphertext, $configured) implements RelayClientInterface {
            /** @var array<int, string> */
            public array $sent = [];

            public function __construct(private readonly ?string $failOnCiphertext, private readonly bool $configured)
            {
            }

            public function isConfigured(): bool
            {
                return $this->configured;
            }

            public function send(string $instanceIdentifier, string $ciphertext): bool
            {
                $this->sent[] = $ciphertext;

                return $ciphertext !== $this->failOnCiphertext;
            }
        };
    }
}
