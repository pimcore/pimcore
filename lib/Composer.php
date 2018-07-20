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

namespace Pimcore;

use Composer\Script\Event;
use Composer\Util\Filesystem;

class Composer
{
    /**
     * @param Event $event
     *
     * @return string
     */
    protected static function getRootPath($event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        return $rootPath;
    }

    /**
     * @param Event $event
     */
    public static function postCreateProject(Event $event)
    {
        $rootPath = self::getRootPath($event);

        self::parametersYmlCheck($rootPath);

        $filesystem = new Filesystem();
        $cleanupFiles = [
            '.github',
            '.travis',
            '.travis.yml',
            'update-scripts',
        ];

        foreach ($cleanupFiles as $file) {
            $path = $rootPath . '/' . $file;
            if (is_dir($path)) {
                $filesystem->removeDirectory($path);
            } elseif (is_file($path)) {
                $filesystem->unlink($path);
            }
        }
    }

    /**
     * @param Event $event
     */
    public static function postInstall(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param string $rootPath
     */
    public static function parametersYmlCheck($rootPath)
    {
        // ensure that there's a parameters.yml, if not we'll create a temporary one, so that the requirement check works
        $parametersYml = $rootPath . '/app/config/parameters.yml';
        $parametersYmlExample = $rootPath . '/app/config/parameters.example.yml';
        if (!file_exists($parametersYml) && file_exists($parametersYmlExample)) {
            copy($parametersYmlExample, $parametersYml);
        }
    }

    /**
     * @param $rootPath
     */
    public static function zendFrameworkOptimization($rootPath)
    {
        // @TODO: Remove in 6.0

        // strips all require_once out of the sources
        // see also: http://framework.zend.com/manual/1.10/en/performance.classloading.html#performance.classloading.striprequires.sed
        $zfPath = $rootPath . '/vendor/zendframework/zendframework1/library/Zend/';

        if (is_dir($zfPath)) {
            $directory = new \RecursiveDirectoryIterator($zfPath);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

            $excludePatterns = [
                '/Loader/Autoloader.php$',
                '/Loader/ClassMapAutoloader.php$',
                '/Application.php$',
            ];

            foreach ($regex as $file) {
                $file = $file[0];

                $excluded = false;
                foreach ($excludePatterns as $pattern) {
                    if (preg_match('@' . $pattern . '@', $file)) {
                        $excluded = true;
                        break;
                    }
                }

                if (!$excluded) {
                    $content = file_get_contents($file);
                    $content = preg_replace('@([^/])(require_once)@', '$1//$2', $content);
                    file_put_contents($file, $content);
                }
            }
        }
    }
}
