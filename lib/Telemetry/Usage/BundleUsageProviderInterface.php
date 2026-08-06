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

/**
 * Extension point that lets a bundle report whether it is actually **used**, not merely installed.
 *
 * A bundle (or a core capability) implements this and is auto-discovered - exactly like
 * {@see \Pimcore\Telemetry\Snapshot\SnapshotCollectorInterface}, with zero core changes. The
 * "am I used?" logic lives entirely in the implementer: it is free to decide what "used" means and
 * when it flips back to false - typically a cheap, content-never structural check ("a configuration
 * exists", "at least one workflow is defined"). The result feeds the {@see BundleUsageCollector},
 * which emits a `usage.<bundleKey>` boolean into the snapshot for the installed-vs-used cross-reference.
 *
 * @internal
 */
interface BundleUsageProviderInterface
{
    /**
     * Stable, content-never key identifying the bundle/capability, e.g. `datahub`. Becomes the
     * snapshot property `usage.<bundleKey>`.
     */
    public function getBundleKey(): string;

    /**
     * Whether this bundle/capability is actually used on this instance. Should be cheap (runs on the
     * periodic snapshot) and must not throw - a failure is treated as "not used".
     */
    public function isUsed(): bool;
}
