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

namespace Pimcore\Telemetry\Spool;

/**
 * The write side of the telemetry outbox. {@see Telemetry} depends only on this narrow seam - it
 * produces events and never claims, acks, or garbage-collects - which keeps the collector unit
 * testable without a database and honours interface segregation.
 *
 * @internal
 */
interface TelemetrySpoolWriterInterface
{
    /**
     * Append already-sanitized, content-never events to the durable outbox. Best-effort and bounded:
     * once the pending backlog reaches the cap, new events are shed rather than grow without limit.
     *
     * @param list<array<string, mixed>> $events
     */
    public function enqueue(array $events, ?int $cap = null): void;
}
