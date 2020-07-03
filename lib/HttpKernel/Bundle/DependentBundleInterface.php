<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\Bundle;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;

/**
 * Defines a bundle which has dependencies on other bundles. When adding a DependentBundle to the collection, the
 * collection will call the static method to register additional bundles.
 */
interface DependentBundleInterface
{
    /**
     * Register bundles to collection.
     *
     * WARNING: this method will be called as soon as this bundle is added to the collection, independent if
     * it will finally be included due to environment restrictions. If you need to load your dependencies conditionally,
     * specify the environments to use on the collection item.
     *
     * @param BundleCollection $collection
     */
    public static function registerDependentBundles(BundleCollection $collection);
}
