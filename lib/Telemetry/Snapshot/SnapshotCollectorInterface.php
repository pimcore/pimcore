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
 * Extension point for the telemetry snapshot (Layer 1). Any bundle can contribute
 * structural, anonymized metrics by implementing this interface and tagging the service
 * with `pimcore.telemetry.snapshot_collector`; the {@see SnapshotBuilder} gathers all
 * registered collectors into a single periodic snapshot.
 *
 * Implementations MUST return safe data only: counts, buckets, booleans, categoricals,
 * versions - never class/field/product names, domains, or any customer content.
 *
 * This is a public extension point and therefore intentionally not `@internal`.
 */
interface SnapshotCollectorInterface
{
    /**
     * Short, stable key the builder uses to namespace this collector's metrics
     * (e.g. "core" -> "core.asset_count_bucket").
     */
    public function getNamespace(): string;

    /**
     * @return array<string, mixed> flat map of safe metric name => value
     */
    public function collect(): array;
}
