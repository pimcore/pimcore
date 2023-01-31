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

        $installableBundles = [];
        foreach($bundles as $index => $bundle) {
            if (array_key_exists($index, Installer::INSTALLABLE_BUNDLES)) {
                $installableBundles[$bundle] = ['all' => true];
            }
        }
        File::putPhpFile($bundlesPhpFile, to_php_data_file_format($installableBundles));
    }
}
