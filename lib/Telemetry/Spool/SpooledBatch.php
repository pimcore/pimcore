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

use function count;

/**
 * A leased set of spooled events: the events to forward plus the {@see $nonce} that identifies the
 * lease. The drainer forwards the events, then calls {@see TelemetrySpool::ack()} with the nonce to
 * delete them - or {@see TelemetrySpool::release()} to hand them back on failure.
 *
 * @internal
 */
final readonly class SpooledBatch
{
    /**
     * @param list<array<string, mixed>> $events
     */
    public function __construct(
        public string $nonce,
        public array $events,
    ) {
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }
}
