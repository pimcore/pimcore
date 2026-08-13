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

namespace Pimcore\Telemetry\Usage;

use Exception;
use Pimcore\Telemetry\Snapshot\SnapshotCollectorInterface;

/**
 * Aggregates every tagged {@see BundleUsageProviderInterface} into the `usage.*` snapshot namespace -
 * one `usage.<bundleKey>` boolean per provider. Cross-referenced with `core.bundles` (installed) in
 * PostHog, `installed && usage=false` is the "installed but not used" signal (stakeholder Q12).
 *
 * Absence means unknown, and there are three ways to be absent: no provider at all (usage reporting is
 * opt-in per bundle), a provider that answers null because it could not reach its own configuration,
 * and a provider that throws. All three are treated alike on purpose - reporting any of them as
 * `false` would be indistinguishable from a genuine "installed but not used", which is the one signal
 * this namespace exists to produce.
 *
 * @internal
 */
final readonly class BundleUsageCollector implements SnapshotCollectorInterface
{
    /**
     * @param iterable<BundleUsageProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {
    }

    public function getNamespace(): string
    {
        return 'usage';
    }

    public function collect(): array
    {
        $usage = [];

        foreach ($this->providers as $provider) {
            try {
                $used = $provider->isUsed();
            } catch (Exception) {
                // A provider must never break the snapshot, and a broken provider knows nothing about
                // adoption - so this is "unknown", not "unused".
                continue;
            }

            if ($used === null) {
                continue;
            }

            $usage[$provider->getBundleKey()] = $used;
        }

        return $usage;
    }
}
