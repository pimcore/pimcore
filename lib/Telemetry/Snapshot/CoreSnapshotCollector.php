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
use Pimcore;
use Pimcore\Tool;
use Pimcore\Version;
use Throwable;
use function count;
use function is_numeric;
use function is_string;

/**
 * Core snapshot collector: versions, environment, bucketed catalog/model sizes, and the set of active
 * bundle names. Structural only - no element names, field names, paths or values.
 *
 * Serves as the reference implementation other bundles copy to add their own metrics.
 *
 * @internal
 */
final readonly class CoreSnapshotCollector implements SnapshotCollectorInterface
{
    /**
     * How wrong an `information_schema.TABLES.TABLE_ROWS` estimate is assumed to be able to be, as a
     * factor in either direction. InnoDB derives it from sampled index pages, so it is an
     * approximation rather than a bounded error; 2x is a deliberately pessimistic working assumption.
     */
    private const ESTIMATE_ERROR_FACTOR = 2;

    public function __construct(
        private ActiveBundles $activeBundles,
        private SnapshotQueryRunner $queryRunner,
        private Bucketizer $bucketizer,
        private string $environment,
        private bool $debugMode = false,
        private string $timezone = '',
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
            'pimcore_git_hash' => $this->getGitHash(),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->getMysqlVersion(),
            'environment_name' => $this->environment,
            'timezone' => $this->timezone !== '' ? $this->timezone : date_default_timezone_get(),
            'kernel_debug' => $this->debugMode,
            'dev_mode' => Pimcore::inDevMode(),
            'system_languages' => Tool::getValidLanguages(),
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
     * Empty when the install has no VCS reference to report.
     *
     * {@see Version::getRevision()} declares `string` but returns `InstalledVersions::getReference()`,
     * which is nullable - an install without a reference makes it raise a TypeError rather than return
     * null. That is an Error, not an Exception, and it would be caught only by the snapshot builder's
     * outer boundary, costing the entire `core.*` namespace over a missing git hash.
     */
    private function getGitHash(): string
    {
        try {
            return Version::getRevision();
        } catch (Throwable) {
            return '';
        }
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
     * Element-table size for bucketing. Prefers the optimizer's cached row estimate (O(1), no scan)
     * so a multi-million-row table costs nothing, and falls back to an exact COUNT(*) when the
     * estimate is not good enough to bucket confidently.
     *
     * "Good enough" is derived from the bucket scale rather than hardcoded: an InnoDB row estimate can
     * be off by a wide margin, so it is only trusted when scaling it by {@see self::ESTIMATE_ERROR_FACTOR}
     * in either direction still lands in the same bucket. That keeps the optimisation correct whatever
     * the buckets are - the previous fixed threshold silently became wrong the moment the scale was
     * widened, because it was chosen for a top bucket that no longer starts there. Very large tables,
     * the ones this exists for, fall in the open-ended top bucket and are always trusted.
     */
    private function countTable(string $table): int
    {
        $estimate = $this->estimateRows($table);

        if ($estimate > 0 && $this->bucketIsUnambiguous($estimate)) {
            return $estimate;
        }

        // An estimate straddling a bucket boundary is counted exactly - and that count can now land on
        // a large table (anything within the error margin of a boundary), so it can hit the statement
        // timeout. Falling back to 0 there would report a million-element catalog as empty, so a failed
        // count yields to the estimate: coarse, but the right order of magnitude. A real empty table
        // still returns an honest 0, because only a failure produces null.
        return $this->exactCount($table) ?? $estimate;
    }

    /**
     * Whether an estimate lands in the same bucket even when it is wrong by the assumed margin.
     */
    private function bucketIsUnambiguous(int $estimate): bool
    {
        $low = (int) ($estimate / self::ESTIMATE_ERROR_FACTOR);
        $high = $estimate * self::ESTIMATE_ERROR_FACTOR;

        return $this->bucketizer->bucket($low) === $this->bucketizer->bucket($high);
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

    /**
     * @return int|null null when the count could not be obtained (timeout, driver error) - distinct
     *                  from a table that genuinely holds 0 rows
     */
    private function exactCount(string $table): ?int
    {
        try {
            $count = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier($table)
            );

            return is_numeric($count) ? (int)$count : null;
        } catch (Exception) {
            return null;
        }
    }
}
