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

use Pimcore\Maintenance\Tasks\TelemetrySnapshotTask;
use Pimcore\Telemetry\Snapshot\SnapshotBuilder;
use Pimcore\Telemetry\Snapshot\TelemetrySnapshotStateInterface;
use Pimcore\Telemetry\TelemetryInterface;
use Pimcore\Tests\Support\Test\TestCase;

class TelemetrySnapshotTaskTest extends TestCase
{
    private const INTERVAL = 3600;

    private function telemetry(bool $enabled): TelemetryInterface
    {
        return new class($enabled) implements TelemetryInterface {
            public int $flushes = 0;

            /** @var list<string> */
            public array $events = [];

            public function __construct(private readonly bool $enabled)
            {
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function capture(string $event, array $properties = [], array $groups = []): void
            {
                $this->events[] = $event;
            }

            public function groupIdentify(string $type, string $key, array $properties): void
            {
            }

            public function flush(): void
            {
                $this->flushes++;
            }
        };
    }

    private function state(?int $lastAt): TelemetrySnapshotStateInterface
    {
        return new class($lastAt) implements TelemetrySnapshotStateInterface {
            public ?int $marked = null;

            public function __construct(private readonly ?int $lastAt)
            {
            }

            public function getLastSnapshotAt(): ?int
            {
                return $this->lastAt;
            }

            public function markSnapshotTaken(int $timestamp): void
            {
                $this->marked = $timestamp;
            }
        };
    }

    private function task(TelemetryInterface $telemetry, TelemetrySnapshotStateInterface $state): TelemetrySnapshotTask
    {
        return new TelemetrySnapshotTask($telemetry, new SnapshotBuilder([]), $state, 'inst-1', self::INTERVAL);
    }

    public function testProducesWhenNeverTakenBefore(): void
    {
        $telemetry = $this->telemetry(true);
        $state = $this->state(null);

        $this->task($telemetry, $state)->execute();

        $this->assertSame(1, $telemetry->flushes);
        $this->assertNotNull($state->marked);
        $this->assertContains('instance.snapshot', $telemetry->events);
    }

    public function testThrottledWithinInterval(): void
    {
        $telemetry = $this->telemetry(true);
        $state = $this->state(time() - 100);

        $this->task($telemetry, $state)->execute();

        $this->assertSame(0, $telemetry->flushes);
        $this->assertNull($state->marked);
    }

    public function testProducesWhenStale(): void
    {
        $telemetry = $this->telemetry(true);
        $state = $this->state(time() - (self::INTERVAL + 400));

        $this->task($telemetry, $state)->execute();

        $this->assertSame(1, $telemetry->flushes);
        $this->assertNotNull($state->marked);
    }

    public function testNoOpWhenDisabled(): void
    {
        $telemetry = $this->telemetry(false);
        $state = $this->state(null);

        $this->task($telemetry, $state)->execute();

        $this->assertSame(0, $telemetry->flushes);
        $this->assertNull($state->marked);
    }
}
