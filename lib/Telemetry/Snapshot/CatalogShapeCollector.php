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

use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;

/**
 * Evidence for "what is the shape of the managed catalog/content landscape?" (EM question #3).
 *
 * Emits the *shape* of the catalog - how deep the element hierarchies go, how products fan out into
 * variants, and how wide folders get - to complement the element *counts* already emitted by
 * {@see CoreSnapshotCollector} and {@see PillarUsageCollector}. The scale facets of #3 (sizes,
 * asset volumes) are answered by those; this adds only depth/variant/organization shape.
 *
 * Everything is content-never: counts and small depth integers only - never a path, name,
 * or value. All figures come from {@see ElementStatisticsProviderInterface}: in the SQL default these
 * are the snapshot's heaviest queries (path-depth and GROUP BY aggregates that no MySQL index serves,
 * time-boxed so they can never stall the run); when Studio's decorating provider is active they are
 * served as cheap search-index aggregations that never touch the transactional DB.
 *
 * "Likely vertical inferred from configured standards" (the remaining #3 facet) is intentionally NOT
 * collected here - it needs domain-revealing signals the content-never contract excludes; see the
 * design doc for the deferred, privacy-safe approach.
 *
 * Increment {@see self::SCHEMA_VERSION} whenever the emitted set changes.
 *
 * @internal
 */
final readonly class CatalogShapeCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private ElementStatisticsProviderInterface $statistics,
    ) {
    }

    public function getNamespace(): string
    {
        return 'catalog';
    }

    public function collect(): array
    {
        $objectDepth = $this->statistics->treeDepth(ElementKind::DataObject);

        return [
            'schema_version' => self::SCHEMA_VERSION,

            // Tree shape - how deep each element hierarchy goes.
            'object_tree_max_depth' => $objectDepth->max,
            'object_tree_avg_depth' => $objectDepth->avg,
            'asset_tree_max_depth' => $this->statistics->treeDepth(ElementKind::Asset)->max,
            'document_tree_max_depth' => $this->statistics->treeDepth(ElementKind::Document)->max,

            // Product/variant shape.
            'products_with_variants' => $this->statistics->objectsWithVariants(),
            'max_variants_per_product' => $this->statistics->maxVariantsPerObject(),

            // Organization shape.
            'max_folder_fanout' => $this->statistics->maxObjectFanout(),
        ];
    }
}
