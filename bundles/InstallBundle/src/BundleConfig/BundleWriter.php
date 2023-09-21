<?php

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

namespace Pimcore\Bundle\InstallBundle\BundleConfig;

use Pimcore\File;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BundleWriter
{
    public function addBundlesToConfig(array $bundles, array $availableBundles): void
    {
        $bundlesPhpFile = PIMCORE_PROJECT_ROOT . '/config/bundles.php';

        if (!file_exists($bundlesPhpFile)) {
            throw new FileNotFoundException("File \"$bundlesPhpFile\" not found!");
        }
        $bundlesToInstall = [];
        foreach ($bundles as $bundle) {
            // check against available bundles since they can change
            if (in_array($bundle, $availableBundles)) {
                $bundlesToInstall[$bundle] = ['all' => true];
            }
        }

        // get installed bundles, they have to stay in the bundles.php, but won't be installed a second time
        $enabledBundles = include $bundlesPhpFile;

        if (is_array($enabledBundles) && !empty($enabledBundles)) {
            $bundlesToInstall = array_merge($bundlesToInstall, $enabledBundles);
        }

        File::putPhpFile($bundlesPhpFile, $this->buildContents($bundlesToInstall));
    }

    private function buildContents(array $bundles): string
    {
        $contents = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $contents .= "    $class::class => [";
            foreach ($envs as $env => $value) {
                $booleanValue = var_export($value, true);
                $contents .= "'$env' => $booleanValue, ";
            }
            $contents = substr($contents, 0, -2)."],\n";
        }
        $contents .= "];\n";

        return $contents;
    }
}
