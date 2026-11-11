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
 * The drain side of the telemetry outbox: lease a batch, then either delete it (successfully
 * forwarded) or hand it back (failed). {@see TelemetryOutboxService} depends only on this seam so it
 * can be unit tested without a database, and so the "claim / ack / release" role stays separate from
 * the producer's write-only role ({@see TelemetrySpoolWriterInterface}).
 *
 * @internal
 */
interface TelemetrySpoolReaderInterface
{
    /**
     * Lease the oldest pending events under a fresh nonce for forwarding. Null when nothing pending.
     */
    public function claim(int $limit = 500): ?SpooledBatch;

    /**
     * Delete a leased batch after the relay confirmed it (ack-only-on-success).
     */
    public function ack(string $nonce): int;

    /**
     * Hand a leased batch back to the pending pool (forward failed) for a later retry.
     */
    public function release(string $nonce): int;
}
