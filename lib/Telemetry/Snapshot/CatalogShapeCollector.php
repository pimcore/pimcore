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

use Exception;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use function array_filter;
use function is_numeric;

/**
 * Evidence for "what is the shape of the managed catalog/content landscape?" (EM question #3).
 *
 * Emits the *shape* of the catalog - how deep the element hierarchies go, how products fan out into
 * variants, and how wide folders get - to complement the element *counts* emitted by
 * {@see PillarUsageCollector}, which owns element volume outright. The scale facets of #3 (sizes,
 * asset volumes) are answered there; this adds only depth/variant/organization shape.
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
        private SnapshotQueryRunner $queryRunner,
    ) {
    }

    public function getNamespace(): string
    {
        return 'catalog';
    }

    public function collect(): array
    {
        $objectDepth = $this->statistics->treeDepth(ElementKind::DataObject);

        $metrics = [
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

            // Content richness - how hard the content is worked, as opposed to how much of it exists.
            // All fixed-name tables; a failed count omits its key rather than reporting nothing there.
            'asset_metadata_count' => $this->count('assets_metadata'),
            'document_editable_count' => $this->count('documents_editables'),
            'property_count' => $this->count('properties'),
            'tag_count' => $this->count('tags'),
            'tag_assignment_count' => $this->count('tags_assignment'),
            'note_count' => $this->count('notes'),
            'object_url_slug_count' => $this->count('object_url_slugs'),
        ];

        return array_filter($metrics, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return int|null null when the count could not be obtained (timeout, driver error), which omits
     *                  the key rather than reporting the capability as unused
     */
    private function count(string $table): ?int
    {
        try {
            $value = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier($table)
            );

            return is_numeric($value) ? (int)$value : null;
        } catch (Exception) {
            return null;
        }
    }
}
