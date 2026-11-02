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

namespace Pimcore\Telemetry\Snapshot\Statistics;

use Pimcore\Telemetry\Snapshot\ElementTypeCounts;

/**
 * The structural, data-volume-scaling statistics the snapshot's heaviest collectors need over the
 * objects/assets/documents element tables (counts by type, hierarchy depth, variant/fan-out shape).
 *
 * Extracting these behind an interface lets the data source be swapped without touching the
 * collectors: core ships an always-available SQL implementation ({@see SqlElementStatisticsProvider}),
 * and a bundle that owns a search index (Studio via the Generic Data Index) can decorate this with an
 * aggregation-backed implementation that never touches the transactional DB, falling back to SQL when
 * the index is unavailable.
 *
 * Every implementation is content-never (counts / small integers only) and must degrade gracefully
 * (never throw) so a statistics failure can only cost precision, never the snapshot.
 *
 * @internal
 */
interface ElementStatisticsProviderInterface
{
    /**
     * Row count per element subtype (e.g. image/video/… for assets, object/variant/folder for objects).
     */
    public function typeCounts(ElementKind $kind): ElementTypeCounts;

    /**
     * Hierarchy depth (max and average) for the element kind, in slash-count semantics.
     */
    public function treeDepth(ElementKind $kind): TreeDepth;

    /**
     * Number of objects that have variants (distinct parents among variant objects).
     */
    public function objectsWithVariants(): int;

    /**
     * Deepest single assortment: the largest number of variants under one parent.
     */
    public function maxVariantsPerObject(): int;

    /**
     * Widest folder: the largest number of child objects under any one parent.
     */
    public function maxObjectFanout(): int;
}
