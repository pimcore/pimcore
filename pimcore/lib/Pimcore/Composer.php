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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Installer\PackageEvent;

class Composer
{
    /**
     * @param Event $event
     */
    public static function postCreateProject(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        // cleanup
        @unlink($rootPath . '/.travis.yml');

        if (!is_dir($rootPath . '/plugins')) {
            rename($rootPath . '/plugins_example', $rootPath . '/plugins');
        }

        if (!is_dir($rootPath . '/website')) {
            rename($rootPath . '/website_example', $rootPath . '/website');
        }

        $filesystem = new Filesystem();
        $filesystem->removeDirectory($rootPath . '/update');
        $filesystem->removeDirectory($rootPath . '/build');
        $filesystem->removeDirectory($rootPath . '/tests');
        $filesystem->removeDirectory($rootPath . '/.svn');
        $filesystem->removeDirectory($rootPath . '/.git');
    }

    /**
     * @param Event $event
     */
    public static function postInstall(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param $rootPath
     */
    public static function zendFrameworkOptimization($rootPath)
    {

        // strips all require_once out of the sources
        // see also: http://framework.zend.com/manual/1.10/en/performance.classloading.html#performance.classloading.striprequires.sed

        $zfPath = $rootPath . "/vendor/zendframework/zendframework1/library/Zend/";

        $directory = new \RecursiveDirectoryIterator($zfPath);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $excludePatterns = [
            "/Loader/Autoloader.php$",
            "/Loader/ClassMapAutoloader.php$",
            "/Application.php$",
        ];

        foreach ($regex as $file) {
            $file = $file[0];

            $excluded = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match("@".$pattern."@", $file)) {
                    $excluded = true;
                    break;
                }
            }

            if (!$excluded) {
                $content = file_get_contents($file);
                $content = preg_replace("@([^/])(require_once)@", "$1//$2", $content);
                file_put_contents($file, $content);
            }
        }
    }
}
