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

use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Item extends AbstractItem
{
    /**
     * @var BundleInterface
     */
    private $bundle;

    /**
     * @param BundleInterface $bundle
     * @param int $priority
     * @param array $environments
     * @param string $source
     */
    public function __construct(
        BundleInterface $bundle,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        $this->bundle = $bundle;

        parent::__construct($priority, $environments, $source);
    }

    /**
     * @return string
     */
    public function getBundleIdentifier(): string
    {
        return get_class($this->bundle);
    }

    /**
     * @return BundleInterface
     */
    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }

    /**
     * @return bool
     */
    public function isPimcoreBundle(): bool
    {
        return $this->bundle instanceof PimcoreBundleInterface;
    }

    /**
     * @param BundleCollection $collection
     */
    public function registerDependencies(BundleCollection $collection)
    {
        if ($this->bundle instanceof DependentBundleInterface) {
            $this->bundle::registerDependentBundles($collection);
        }
    }
}
