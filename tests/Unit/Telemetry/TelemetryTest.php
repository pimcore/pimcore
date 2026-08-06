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

use Pimcore\Telemetry\EventSanitizer;
use Pimcore\Telemetry\Spool\TelemetrySpoolWriterInterface;
use Pimcore\Telemetry\Telemetry;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use stdClass;

// Records the events handed to the spool instead of persisting them.
final class RecordingSpoolWriter implements TelemetrySpoolWriterInterface
{
    /** @var list<array<string, mixed>> */
    public array $enqueued = [];

    public function enqueue(array $events, ?int $cap = null): void
    {
        foreach ($events as $event) {
            $this->enqueued[] = $event;
        }
    }
}

class TelemetryTest extends TestCase
{
    private const INSTANCE = 'instance-1';

    private const PRODUCT_KEY = 'product-key-1';

    public function testFlushPersistsBufferedEventsToTheSpool(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created', ['element_type' => 'object']);
        $this->assertSame([], $spool->enqueued, 'nothing is spooled before flush');

        $telemetry->flush();

        $this->assertCount(1, $spool->enqueued);
        $this->assertSame('object.created', $spool->enqueued[0]['event']);
        $this->assertSame(self::INSTANCE, $spool->enqueued[0]['groups']['instance']);
    }

    public function testFlushClearsTheBuffer(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created');
        $telemetry->flush();
        $telemetry->flush();

        $this->assertCount(1, $spool->enqueued);
    }

    public function testUnidentifiedInstanceSpoolsNothing(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = new Telemetry('', self::PRODUCT_KEY, $spool, new NullLogger(), $this->sanitizer());

        $this->assertFalse($telemetry->isEnabled());

        $telemetry->capture('object.created');
        $telemetry->flush();

        $this->assertSame([], $spool->enqueued);
    }

    public function testMissingProductKeyDisablesTelemetry(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = new Telemetry(self::INSTANCE, '', $spool, new NullLogger(), $this->sanitizer());

        $this->assertFalse($telemetry->isEnabled());

        $telemetry->capture('object.created');
        $telemetry->flush();

        $this->assertSame([], $spool->enqueued);
    }

    public function testObjectPropertiesNeverReachTheSpoolButScalarArraysDo(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created', [
            'element_type' => 'object',
            'bundles' => ['PimcoreCoreBundle', 'PimcoreDataHubBundle'], // scalar array -> kept
            'leaked_object' => new stdClass(),                          // object -> dropped
        ]);
        $telemetry->flush();

        $this->assertSame(
            ['element_type' => 'object', 'bundles' => ['PimcoreCoreBundle', 'PimcoreDataHubBundle']],
            $spool->enqueued[0]['properties']
        );
    }

    private function telemetry(TelemetrySpoolWriterInterface $spool): Telemetry
    {
        return new Telemetry(self::INSTANCE, self::PRODUCT_KEY, $spool, new NullLogger(), $this->sanitizer());
    }

    private function sanitizer(): EventSanitizer
    {
        return new EventSanitizer(new NullLogger());
    }
}
