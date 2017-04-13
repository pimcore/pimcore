<?php
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
use Pimcore\Kernel;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class AppKernel extends Kernel
{
    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles()
    {
        // pimcore bundles
        $bundles = parent::registerBundles();

        if (class_exists('\\AppBundle\\AppBundle')) {
            $bundles[] = new \AppBundle\AppBundle;
        }

        if (class_exists('\Pimcore\Bundle\LegacyBundle\PimcoreLegacyBundle')) {
            $bundles[] = new \Pimcore\Bundle\LegacyBundle\PimcoreLegacyBundle;
        }

        return $bundles;
    }
}
