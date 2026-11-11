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
 * Hands out the next encrypted batch to a drainer and acks/releases it. Both drainers depend on this
 * seam: the maintenance {@see \Pimcore\Maintenance\Tasks\TelemetrySpoolDrainTask} and the Studio
 * backend outbox endpoints. Implemented by {@see TelemetryOutboxService}.
 *
 * @internal
 */
interface TelemetryOutboxInterface
{
    /**
     * Whether the outbox can produce batches (instance identity + product key present).
     */
    public function isReady(): bool;

    /**
     * Claim, wrap, and encrypt the next pending batch, or null when the pool is empty.
     */
    public function nextBatch(): ?EncryptedBatch;

    /**
     * Delete a delivered batch (call only after the relay confirmed it).
     */
    public function ack(string $nonce): int;

    /**
     * Return an undelivered batch to the pool for a later retry.
     */
    public function release(string $nonce): int;
}
