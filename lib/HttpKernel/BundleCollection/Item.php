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

use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Item extends AbstractItem
{
    private BundleInterface $bundle;

    public function __construct(
        BundleInterface $bundle,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        $this->bundle = $bundle;

        parent::__construct($priority, $environments, $source);
    }

    public function getBundleIdentifier(): string
    {
        return get_class($this->bundle);
    }

    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }

    public function isPimcoreBundle(): bool
    {
        return $this->bundle instanceof PimcoreBundleInterface;
    }

    public function registerDependencies(BundleCollection $collection): void
    {
        if ($this->bundle instanceof DependentBundleInterface) {
            $this->bundle::registerDependentBundles($collection);
        }
    }
}
