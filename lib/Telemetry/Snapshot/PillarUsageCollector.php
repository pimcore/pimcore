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
 * *structural evidence* of real usage: bucketed element-type volumes and a few capability bundle
 * flags. The actual pillar classification (and the "combination" label) is deliberately left to
 * the analysis layer (HogQL over these group properties), so the definition can be tuned without
 * shipping a new Pimcore release.
 *
 * Everything here is content-never: counts, buckets, types, and booleans only - never class,
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
        private Bucketizer $bucketizer,
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
            'asset_count_bucket' => $this->bucket($assets->total()),
            'asset_image_count_bucket' => $this->bucket($assets->ofType('image')),
            'asset_video_count_bucket' => $this->bucket($assets->ofType('video')),
            'asset_document_count_bucket' => $this->bucket($assets->ofType('document')),
            'asset_audio_count_bucket' => $this->bucket($assets->ofType('audio')),
            'asset_type_variety' => $assets->distinctTypes(),

            // PIM - modelled data objects, product-like variant depth, and class-model breadth.
            'class_count_bucket' => $this->bucket($this->count('classes')),
            'object_count_bucket' => $this->bucket($objects->ofType('object')),
            'object_variant_count_bucket' => $this->bucket($objects->ofType('variant')),

            // DXP - web documents, page vs. transactional content, and multi-site footprint.
            'document_page_count_bucket' => $this->bucket($documents->ofType('page')),
            'document_email_count_bucket' => $this->bucket($documents->ofType('email')),
            'document_link_count_bucket' => $this->bucket($documents->ofType('link')),
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

    private function bucket(int $count): string
    {
        return $this->bucketizer->bucket($count);
    }
}
