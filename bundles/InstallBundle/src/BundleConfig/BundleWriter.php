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

use Pimcore\File;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @internal
 */
final readonly class BundleWriter
{
    /**
     * @param list<class-string> $bundles
     */
    public function addBundlesToConfig(array $bundles): void
    {
        $bundlesPhpFile = PIMCORE_PROJECT_ROOT . '/config/bundles.php';

        if (!file_exists($bundlesPhpFile)) {
            throw new FileNotFoundException("File \"$bundlesPhpFile\" not found!");
        }

        $bundlesToInstall = [];
        foreach ($bundles as $bundle) {
            $bundlesToInstall[$bundle] = ['all' => true];
        }

        // get installed bundles, they have to stay in the bundles.php, but won't be installed a second time
        $enabledBundles = include $bundlesPhpFile;

        if (is_array($enabledBundles) && !empty($enabledBundles)) {
            $bundlesToInstall = array_merge($bundlesToInstall, $enabledBundles);
        }

        File::putPhpFile($bundlesPhpFile, $this->buildContents($bundlesToInstall));
    }

    /**
     * @param array<class-string, array<string, bool>> $bundles
     */
    private function buildContents(array $bundles): string
    {
        $contents = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $contents .= "    $class::class => [";
            foreach ($envs as $env => $value) {
                $booleanValue = var_export($value, true);
                $contents .= "'$env' => $booleanValue, ";
            }
            $contents = substr($contents, 0, -2) . "],\n";
        }
        $contents .= "];\n";

        return $contents;
    }
}
