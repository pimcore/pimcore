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
use function is_string;

/**
 * Core snapshot collector: what this installation *is* - versions, environment, and the set of
 * active bundle names. Structural only - no element names, field names, paths or values.
 *
 * Deliberately reports no element or class counts. {@see PillarUsageCollector} owns element volume
 * and already derives it from the shared statistics provider; duplicating it here produced two names
 * for one number (core.asset_count was byte-identical to pillars.asset_count) and made the snapshot
 * run its most expensive aggregation twice.
 *
 * Serves as the reference implementation other bundles copy to add their own metrics.
 *
 * @internal
 */
final readonly class CoreSnapshotCollector implements SnapshotCollectorInterface
{
    public function __construct(
        private ActiveBundles $activeBundles,
        private SnapshotQueryRunner $queryRunner,
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
}
