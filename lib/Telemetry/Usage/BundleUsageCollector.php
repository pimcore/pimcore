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

use Pimcore\Telemetry\Snapshot\SnapshotCollectorInterface;
use Throwable;

/**
 * Aggregates every tagged {@see BundleUsageProviderInterface} into the `usage.*` snapshot namespace -
 * one `usage.<bundleKey>` boolean per provider. Cross-referenced with `core.bundles` (installed) in
 * PostHog, `installed && usage=false` is the "installed but not used" signal (stakeholder Q12).
 *
 * A provider that throws is recorded as `false` rather than breaking the snapshot - telemetry is
 * best-effort. Bundles without a provider are simply absent (unknown), which is intended: usage
 * reporting is opt-in per bundle.
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
            $key = $provider->getBundleKey();

            try {
                $usage[$key] = $provider->isUsed();
            } catch (Throwable) {
                // A provider must never break the snapshot; treat a failure as "not used".
                $usage[$key] = false;
            }
        }

        return $usage;
    }
}
