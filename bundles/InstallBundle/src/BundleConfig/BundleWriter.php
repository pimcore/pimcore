<?php

namespace Pimcore\Bundle\InstallBundle\BundleConfig;

use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\File;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BundleWriter
{
    public function addBundlesToConfig(array $bundles): void
    {
        $bundlesPhpFile = PIMCORE_PROJECT_ROOT . '/config/bundles.php';

        if (!file_exists($bundlesPhpFile)) {
            throw new FileNotFoundException();
        }
        $bundlesToInstall = [];
        foreach($bundles as $bundle) {
            if (in_array($bundle, Installer::INSTALLABLE_BUNDLES)) {
                $bundlesToInstall[$bundle] = ['all' => true];
            }
        }

        // get installed bundles, they have to stay in the bundles.php, but won't be installed a second time
        $enabledBundles = include $bundlesPhpFile;

        if(is_array($enabledBundles) && !empty($enabledBundles)) {
            $bundlesToInstall = array_merge($bundlesToInstall, $enabledBundles);
        }

        File::putPhpFile($bundlesPhpFile, to_php_data_file_format($bundlesToInstall));
    }
}
