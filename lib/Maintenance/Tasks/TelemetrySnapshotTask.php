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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Telemetry\Snapshot\SnapshotBuilder;
use Pimcore\Telemetry\Snapshot\TelemetrySnapshotStateInterface;
use Pimcore\Telemetry\TelemetryInterface;
use function time;

/**
 * Produces the periodic deployment snapshot (Layer 1) and adds it to the spool. The snapshot is a
 * slow-changing structural census, so it is **throttled**: even though maintenance may run every few
 * minutes, a new snapshot is only produced once the last one is older than the configured interval.
 * Delivery is a separate concern - the maintenance drain task and the Studio UI ship whatever is in
 * the spool (snapshots and behavioral events alike) on their own cadence.
 *
 * @internal
 */
final readonly class TelemetrySnapshotTask implements TaskInterface
{
    private const EVENT_INSTANCE_SNAPSHOT = 'instance.snapshot';

    private const INSTANCE_GROUP_TYPE = 'instance';

    public function __construct(
        private TelemetryInterface $telemetry,
        private SnapshotBuilder $builder,
        private TelemetrySnapshotStateInterface $state,
        private string $instanceIdentifier,
        private int $intervalSeconds,
    ) {
    }

    public function execute(): void
    {
        if (!$this->telemetry->isEnabled()) {
            return;
        }

        $now = time();
        $lastAt = $this->state->getLastSnapshotAt();

        if ($lastAt !== null && ($now - $lastAt) < $this->intervalSeconds) {
            // A recent snapshot already captures the (slow-changing) structure - skip this run.
            return;
        }

        $snapshot = $this->builder->build();
        $this->telemetry->groupIdentify(self::INSTANCE_GROUP_TYPE, $this->instanceIdentifier, $snapshot);
        $this->telemetry->capture(self::EVENT_INSTANCE_SNAPSHOT, $snapshot);
        $this->telemetry->flush();

        $this->state->markSnapshotTaken($now);
    }
}
