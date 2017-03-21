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

namespace Pimcore\Extension\Bundle;

use Pimcore\Extension\Bundle\Exception\RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PimcoreBundleLocator
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var bool
     */
    private $handleComposer = true;

    /**
     * @param array $paths
     * @param bool $handleComposer
     */
    public function __construct(array $paths = [], $handleComposer = true)
    {
        $this->paths          = $paths;
        $this->handleComposer = $handleComposer;
    }

    /**
     * Locate pimcore bundles in configured paths
     *
     * @return array A list of found bundle class names
     */
    public function findBundles()
    {
        $result = $this->findBundlesInPaths($this->paths);
        if ($this->handleComposer) {
            $result = array_merge($result, $this->findComposerBundles());
        }

        $result = array_values($result);
        sort($result);

        return $result;
    }

    /**
     * @param array $paths
     *
     * @return array
     */
    private function findBundlesInPaths(array $paths)
    {
        $filteredPaths = [];
        foreach ($paths as $path) {
            if (file_exists($path) && is_dir($path)) {
                $filteredPaths[] = $path;
            }
        }

        $result = [];

        $finder = new Finder();
        $finder
            ->in(array_unique($filteredPaths))
            ->name('*Bundle.php');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $className = $this->findClassName($file);
            if ($className) {
                $this->processBundleClass($className, $result);
            }
        }

        return $result;
    }

    /**
     * Finds composer bundles in /vendor with the following prerequisites:
     *
     *  * Composer package type is "pimcore-bundle"
     *  * If the [ extra: [ pimcore: [ bundles: [] ] ] entry is available in the config, it will use this config
     *    as list of available bundle names
     *  * If the config entry above is not available, it will scan the package directory with the same logic as for
     *    the other paths
     *
     * @return array
     */
    private function findComposerBundles()
    {
        $json = $this->readComposerConfig();
        if (!$json) {
            return [];
        }

        $composerPaths = [];

        $result = [];
        foreach ($json as $packageInfo) {
            if ($packageInfo['type'] !== 'pimcore-bundle') {
                continue;
            }

            // if bundle explicitely defines bundles, use the config
            if (isset($packageInfo['extra']) && isset($packageInfo['extra']['pimcore'])) {
                $cfg = $packageInfo['extra']['pimcore'];
                if (isset($cfg['bundles']) && is_array($cfg['bundles'])) {
                    foreach ($cfg['bundles'] as $bundle) {
                        $this->processBundleClass($bundle, $result);
                    }
                }
            } else {
                // add path to list of composer paths which will be processed via path search
                $composerPaths[] = PIMCORE_COMPOSER_PATH . '/' . $packageInfo['name'];
            }
        }

        // wildcard process composer paths which didn't have a dedicated bundle config entry
        if (count($composerPaths) > 0) {
            $result = array_merge($result, $this->findBundlesInPaths($composerPaths));
        }

        return $result;
    }

    /**
     * @return array|null
     */
    private function readComposerConfig()
    {
        // try to read composer.lock first
        $json = $this->readComposerFile([PIMCORE_PROJECT_ROOT, 'composer.lock']);
        if ($json && isset($json['packages']) && is_array($json['packages'])) {
            return $json['packages'];
        }

        // try to read vendor/composer/installed.json as fallback
        $json = $this->readComposerFile([PIMCORE_COMPOSER_PATH, 'composer', 'installed.json']);
        if ($json && is_array($json)) {
            return $json;
        }
    }

    /**
     * @param array $path
     *
     * @return array|null
     */
    private function readComposerFile(array $path)
    {
        $path = implode(DIRECTORY_SEPARATOR, $path);
        if (file_exists($path) && is_readable($path)) {
            $json = json_decode(file_get_contents($path), true);

            if (null === $json) {
                throw new RuntimeException(sprintf('Failed to parse composer file %s', $path));
            }

            return $json;
        }
    }

    /**
     * @param string $bundle
     * @param array $result
     */
    private function processBundleClass($bundle, array &$result)
    {
        if (empty($bundle) || !is_string($bundle)) {
            return;
        }

        if (!class_exists($bundle)) {
            return;
        }

        $reflector = new \ReflectionClass($bundle);
        if (!$reflector->isInstantiable() || !$reflector->implementsInterface(PimcoreBundleInterface::class)) {
            return;
        }

        $result[$reflector->getName()] = $reflector->getName();
    }

    /**
     * Finds the fully qualified class name from a given PHP file by parsing the file content
     *
     * @see http://jarretbyrne.com/2015/06/197/
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function findClassName(SplFileInfo $file)
    {
        $namespace = '';
        $class     = '';

        $gettingNamespace = false;
        $gettingClass     = false;

        foreach (token_get_all($file->getContents()) as $token) {
            // start collecting as soon as we find the namespace token
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $gettingNamespace = true;
            } else if (is_array($token) && $token[0] === T_CLASS) {
                $gettingClass = true;
            }

            if ($gettingNamespace) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    // append to namespace
                    $namespace .= $token[1];
                } else if ($token === ';') {
                    // namespace done
                    $gettingNamespace = false;
                }
            }

            if ($gettingClass) {
                if (is_array($token) && $token[0] === T_STRING) {
                    $class = $token[1];

                    // all done
                    break;
                }
            }
        }

        if (empty($class)) {
            throw new RuntimeException(sprintf('Failed to get find class name in file %s', $file->getPathInfo()));
        }

        return empty($namespace) ? $class : $namespace . '\\' . $class;
    }
}
