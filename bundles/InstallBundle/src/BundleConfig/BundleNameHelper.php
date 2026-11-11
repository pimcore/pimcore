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

namespace Pimcore\Bundle\InstallBundle\BundleConfig;

/**
 * Extract short bundle name from a fully-qualified class name.
 *
 * @internal
 */
final class BundleNameHelper
{
    /**
     * Extract the short class name from a FQCN.
     *
     * e.g. "Pimcore\Bundle\SeoBundle\PimcoreSeoBundle" -> "PimcoreSeoBundle"
     */
    public static function getShortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
