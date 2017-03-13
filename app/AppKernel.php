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
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $customBundles = [
            new \AppBundle\AppBundle(),
        ];

        if(class_exists('\PimcoreLegacyBundle\PimcoreLegacyBundle')) {
            $customBundles[] = new \PimcoreLegacyBundle\PimcoreLegacyBundle;
        }

        $bundles = array_merge(parent::registerBundles(), $customBundles);

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return PIMCORE_APP_ROOT;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return PIMCORE_PRIVATE_VAR . '/cache/' . $this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return PIMCORE_LOG_DIRECTORY;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
