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

namespace Pimcore\HttpKernel\BundleCollection;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface ItemInterface
{
    const SOURCE_PROGRAMATICALLY = 'programatically';

    const SOURCE_EXTENSION_MANAGER_CONFIG = 'extension_manager_config';

    public function getBundleIdentifier(): string;

    public function getBundle(): BundleInterface;

    public function isPimcoreBundle(): bool;

    public function getPriority(): int;

    /**
     * @return string[]
     */
    public function getEnvironments(): array;

    /**
     * Registers dependent bundles if the bundle implements DependentBundleInterface
     */
    public function registerDependencies(BundleCollection $collection): void;

    public function matchesEnvironment(string $environment): bool;

    public function getSource(): string;
}
