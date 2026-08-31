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
use function is_numeric;

/**
 * Evidence for "which Pimcore pillars does each customer actually use?" (EM question #1).
 *
 * The pillars (DAM, PIM, MDM, DXP, Commerce) are not bundles - they are core capabilities every
 * install technically has - so we cannot answer by checking a feature flag. Instead we emit the
 * *structural evidence* of real usage: element-type volumes and a few capability bundle
 * flags. The actual pillar classification (and the "combination" label) is deliberately left to
 * the analysis layer (HogQL over these group properties), so the definition can be tuned without
 * shipping a new Pimcore release.
 *
 * Everything here is content-never: counts, types, and booleans only - never class,
 * product, asset, document, or field names. Per-element-kind counts come from
 * {@see ElementStatisticsProviderInterface} - a single type aggregation per kind (a SQL `GROUP BY
 * type`, or a search-index terms aggregation when Studio's decorating provider is active) yielding
 * every per-type count, the type variety, and the total. Runs on the periodic maintenance snapshot
 * only; failures degrade to zero, never an exception.
 *
 * Increment {@see self::SCHEMA_VERSION} whenever the emitted evidence set changes, so the analysis
 * layer can tell which signals a given snapshot carried.
 *
 * @internal
 */
final readonly class PillarUsageCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private ActiveBundles $activeBundles,
        private SnapshotQueryRunner $queryRunner,
        private ElementStatisticsProviderInterface $statistics,
    ) {
    }

    public function getNamespace(): string
    {
        return 'pillars';
    }

    public function collect(): array
    {
        // One aggregation per element kind (SQL GROUP BY, or a search-index terms aggregation when a
        // decorating provider is active) - every per-type count below is read from these.
        $assets = $this->statistics->typeCounts(ElementKind::Asset);
        $objects = $this->statistics->typeCounts(ElementKind::DataObject);
        $documents = $this->statistics->typeCounts(ElementKind::Document);

        return [
            'schema_version' => self::SCHEMA_VERSION,

            // DAM - digital asset volume and the variety of rich-media types managed.
            'asset_count' => $assets->total(),
            'asset_image_count' => $assets->ofType('image'),
            'asset_video_count' => $assets->ofType('video'),
            'asset_document_count' => $assets->ofType('document'),
            'asset_audio_count' => $assets->ofType('audio'),
            'asset_type_variety' => $assets->distinctTypes(),

            // PIM - modelled data objects, product-like variant depth, and class-model breadth.
            // `object_count` is plain objects only; `object_total_count` is every row in the table
            // (objects + variants + folders), which is what core.* used to report separately.
            // Assets need no equivalent: `asset_count` above is already the table-wide total.
            'class_count' => $this->count('classes'),
            'object_count' => $objects->ofType('object'),
            'object_variant_count' => $objects->ofType('variant'),
            'object_total_count' => $objects->total(),

            // DXP - web documents, page vs. transactional content, and multi-site footprint.
            'document_page_count' => $documents->ofType('page'),
            'document_email_count' => $documents->ofType('email'),
            'document_link_count' => $documents->ofType('link'),
            'document_total_count' => $documents->total(),
            // Exact count of Site entities. The legacy StatisticsManager appeared to disagree here -
            // it reported `sites: 0` alongside a non-empty `sites_domains` - but its table rows came
            // from information_schema.TABLE_ROWS, an InnoDB estimate that commonly reads 0 for small
            // tables. The estimate was wrong; this count is not.
            'site_count' => $this->count('sites'),
            'seo_bundle_active' => $this->activeBundles->has('Seo'),
            'personalization_bundle_active' => $this->activeBundles->has('Personalization'),
            'headless_documents_bundle_active' => $this->activeBundles->has('HeadlessDocuments'),
            'portal_engine_bundle_active' => $this->activeBundles->has('PortalEngine'),

            // Commerce - capability signal only. "Actually transacting" (order/product object
            // counts) belongs in a collector shipped by the ecommerce bundle itself, not core.
            'commerce_bundle_active' => $this->activeBundles->has('Ecommerce'),

            // Integration - Data Hub as a cross-cutting maturity signal (see also question #5).
            'datahub_bundle_active' => $this->activeBundles->has('DataHub'),
        ];
    }

    /**
     * Low-cardinality COUNT(*) for tables that are not part of the element index (classes, sites);
     * these are tiny, so a direct count is fine. Time-boxed; degrades to 0 on failure.
     */
    private function count(string $table): int
    {
        try {
            $count = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier($table)
            );

            return is_numeric($count) ? (int)$count : 0;
        } catch (Exception) {
            return 0;
        }
    }
}
