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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\HttpKernel;

use InvalidArgumentException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Builds public web path for a resource saved in Resources/public of a bundle
 *
 * @see http://stackoverflow.com/questions/21017639/get-path-of-image-in-specified-bundle
 *
 * @internal
 */
class WebPathResolver
{
    /**
     * Gets the prefix of the asset with the given bundle
     *
     * @param BundleInterface $bundle Bundle to fetch in
     *
     * @throws InvalidArgumentException
     *
     * @return string Prefix
     */
    public function getPrefix(BundleInterface $bundle): string
    {
        if (!is_dir($bundle->getPath() . '/Resources/public') && !is_dir($bundle->getPath() . '/public')) {
            throw new InvalidArgumentException(sprintf(
                'Bundle %s does not have Resources/public folder',
                $bundle->getName()
            ));
        }

        return sprintf(
            '/bundles/%s',
            preg_replace('/bundle$/', '', strtolower($bundle->getName()))
        );
    }

    /**
     * Get path
     *
     * @param BundleInterface $bundle Bundle to fetch in
     * @param string $type Which folder to fetch in (image, css..)
     * @param string $resource Resource (image1.png)
     *
     * @return string Resolved path
     */
    public function getPath(BundleInterface $bundle, string $type, string $resource): string
    {
        $prefix = $this->getPrefix($bundle);

        return sprintf('%s/%s/%s', $prefix, $type, $resource);
    }
}
