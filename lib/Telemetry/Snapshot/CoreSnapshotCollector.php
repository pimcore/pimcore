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
use Pimcore\Tool;
use Pimcore\Version;
use function count;
use function is_numeric;
use function is_string;
use function strtolower;
use function trim;

/**
 * Core snapshot collector: versions, environment, bucketed catalog/model sizes, and the
 * set of active bundle names. Behavior- and structure-only - no names, domains, or content.
 *
 * Serves as the reference implementation other bundles copy to add their own metrics.
 *
 * @internal
 */
final readonly class CoreSnapshotCollector implements SnapshotCollectorInterface
{
    /**
     * Above this the optimizer's row estimate already lands in the top ("1000+") bucket, so we trust
     * it and skip a full `COUNT(*)` on a potentially huge table. At or below it an exact count is
     * cheap (small table) and keeps the bucket precise where precision actually matters. InnoDB
     * estimates are never off by the >80% it would take to misclassify across this margin.
     */
    private const ESTIMATE_TRUST_THRESHOLD = 5000;

    public function __construct(
        private ActiveBundles $activeBundles,
        private SnapshotQueryRunner $queryRunner,
        private Bucketizer $bucketizer,
        private string $environment,
    ) {
    }

    public function getNamespace(): string
    {
        return 'core';
    }

    public function collect(): array
    {
        // Only first-party bundles are named; customer/agency bundles are counted (see ActiveBundles).
        $bundles = $this->activeBundles->firstPartyNames();

        return [
            'pimcore_version' => Version::getVersion(),
            'pimcore_major_version' => Version::getMajorVersion(),
            'pimcore_platform_version' => Version::getPlatformVersion(),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->getMysqlVersion(),
            'mode' => $this->mode(),
            'language_count' => count(Tool::getValidLanguages()),
            'active_bundle_count' => $this->activeBundles->count(),
            'bundles' => $bundles,
            'third_party_bundle_count' => $this->activeBundles->thirdPartyCount(),
            'dataobject_class_count_bucket' => $this->bucketizer->bucket($this->countTable('classes')),
            'object_count_bucket' => $this->bucketizer->bucket($this->countTable('objects')),
            'asset_count_bucket' => $this->bucketizer->bucket($this->countTable('assets')),
            'document_count_bucket' => $this->bucketizer->bucket($this->countTable('documents')),
            'datahub_enabled' => $this->activeBundles->has('DataHub'),
        ];
    }

    /**
     * The Symfony environment, reduced to a fixed vocabulary.
     *
     * `APP_ENV` is customer-authored free text (agency deployments legitimately use values like
     * `prod_<clientname>`), so the raw value is never emitted - that would be the only free-form
     * string in the snapshot and would break the content-never contract. Anything outside the known
     * set collapses to `other`, which is a useful signal in itself.
     */
    private function mode(): string
    {
        return match (strtolower(trim($this->environment))) {
            'prod' => 'prod',
            'dev' => 'dev',
            'test' => 'test',
            default => 'other',
        };
    }

    private function getMysqlVersion(): string
    {
        try {
            $version = $this->queryRunner->fetchOne('SELECT VERSION()');

            return is_string($version) ? $version : 'unknown';
        } catch (Exception) {
            return 'unknown';
        }
    }

    /**
     * Element-table size for bucketing. Uses the optimizer's cached row estimate (O(1), no scan) so
     * a multi-million-row table costs nothing; only small/unknown tables fall back to an exact
     * COUNT(*), where the scan is cheap and the bucket precision is worth it.
     */
    private function countTable(string $table): int
    {
        $estimate = $this->estimateRows($table);

        return $estimate >= self::ESTIMATE_TRUST_THRESHOLD ? $estimate : $this->exactCount($table);
    }

    private function estimateRows(string $table): int
    {
        try {
            $rows = $this->queryRunner->fetchOne(
                'SELECT TABLE_ROWS FROM information_schema.TABLES'
                . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );

            return is_numeric($rows) ? (int)$rows : 0;
        } catch (Exception) {
            return 0;
        }
    }

    private function exactCount(string $table): int
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
