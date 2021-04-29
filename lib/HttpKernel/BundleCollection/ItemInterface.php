<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\BundleCollection;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface ItemInterface
{
    const SOURCE_PROGRAMATICALLY = 'programatically';
    const SOURCE_EXTENSION_MANAGER_CONFIG = 'extension_manager_config';

    /**
     * @return string
     */
    public function getBundleIdentifier(): string;

    /**
     * @return BundleInterface
     */
    public function getBundle(): BundleInterface;

    /**
     * @return bool
     */
    public function isPimcoreBundle(): bool;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @return array
     */
    public function getEnvironments(): array;

    /**
     * Registers dependent bundles if the bundle implements DependentBundleInterface
     *
     * @param BundleCollection $collection
     */
    public function registerDependencies(BundleCollection $collection);

    /**
     * @param string $environment
     * @return bool
     */
    public function matchesEnvironment(string $environment): bool;

    /**
     * @return string
     */
    public function getSource(): string;
}
