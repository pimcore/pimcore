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

use Pimcore\Extension\Bundle\PimcoreBundleManager;
use function count;
use function str_contains;
use function str_starts_with;
use function strrchr;
use function substr;

/**
 * The active bundles, resolved once per snapshot and split into first-party and everything else.
 *
 * {@see PimcoreBundleManager::getActiveBundles()} returns every bundle implementing
 * `PimcoreBundleInterface` - which is precisely how customers and agencies build their own bundles,
 * so the raw list contains customer-authored class names. Only first-party bundles are ever named;
 * anything else is counted but never identified, because a bundle class name is customer content
 * and must not leave the installation.
 *
 * Membership is decided by namespace rather than a per-bundle allow-list, so new Pimcore bundles are
 * covered automatically and an unknown namespace fails safe (counted, not named).
 *
 * Enumerating the bundles costs roughly one statement per installed bundle, so the result is
 * memoized here rather than re-queried by each collector.
 *
 * @internal
 */
final class ActiveBundles
{
    /**
     * Namespaces owned by Pimcore. The two non-`Pimcore\` entries are Pimcore products that predate
     * the current namespace convention.
     */
    private const FIRST_PARTY_NAMESPACES = [
        'Pimcore\\',
        'CustomerManagementFrameworkBundle\\',
        'FrontendPermissionToolkitBundle\\',
    ];

    /**
     * @var list<string>|null
     */
    private ?array $firstParty = null;

    private int $thirdPartyCount = 0;

    public function __construct(
        private readonly PimcoreBundleManager $bundleManager,
    ) {
    }

    /**
     * Short class names of the active first-party bundles - safe to emit.
     *
     * @return list<string>
     */
    public function firstPartyNames(): array
    {
        $this->resolve();

        return $this->firstParty;
    }

    /**
     * How many active bundles are not first-party. A count only: these are customer, agency or
     * third-party bundles and their names never leave the instance.
     */
    public function thirdPartyCount(): int
    {
        $this->resolve();

        return $this->thirdPartyCount;
    }

    public function count(): int
    {
        return count($this->firstPartyNames()) + $this->thirdPartyCount();
    }

    /**
     * Whether an active *first-party* bundle's short name contains the given needle. Third-party
     * bundles are deliberately not considered, so a customer bundle called e.g. `AcmeDataHubSync`
     * can never flip a Pimcore capability flag.
     */
    public function has(string $needle): bool
    {
        foreach ($this->firstPartyNames() as $name) {
            if (str_contains($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function resolve(): void
    {
        if ($this->firstParty !== null) {
            return;
        }

        $firstParty = [];
        $thirdParty = 0;

        foreach ($this->bundleManager->getActiveBundles() as $bundle) {
            $class = $bundle::class;

            if (!$this->isFirstParty($class)) {
                $thirdParty++;

                continue;
            }

            $shortName = strrchr($class, '\\');
            $firstParty[] = $shortName === false ? $class : substr($shortName, 1);
        }

        $this->firstParty = $firstParty;
        $this->thirdPartyCount = $thirdParty;
    }

    private function isFirstParty(string $class): bool
    {
        foreach (self::FIRST_PARTY_NAMESPACES as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
