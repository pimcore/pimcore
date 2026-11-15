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

namespace Pimcore\Telemetry\Snapshot;

/**
 * Persists when the last deployment snapshot was produced, so the snapshot task can throttle itself:
 * the snapshot is a slow-changing structural census, so it is created at most once per configured
 * interval regardless of how often maintenance runs. Draining (delivery) is independent of this.
 *
 * @internal
 */
interface TelemetrySnapshotStateInterface
{
    /**
     * Unix timestamp of the last produced snapshot, or null if one has never been produced.
     */
    public function getLastSnapshotAt(): ?int;

    /**
     * Record that a snapshot was produced at the given Unix timestamp.
     */
    public function markSnapshotTaken(int $timestamp): void;
}
