<?php

/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore;

use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Installer\PackageEvent;

class Composer
{
    public static function postCreateProject(Event $event) {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        // cleanup
        @unlink($rootPath . '/.travis.yml');

        rename($rootPath . '/plugins_example', $rootPath . '/plugins');
        rename($rootPath . '/website_example', $rootPath . '/website');

        $filesystem = new Filesystem();
        $filesystem->removeDirectory($rootPath . '/update');
        $filesystem->removeDirectory($rootPath . '/build');
        $filesystem->removeDirectory($rootPath . '/tests');
        $filesystem->removeDirectory($rootPath . '/.svn');
        $filesystem->removeDirectory($rootPath . '/.git');
    }

    public static function postInstall(Event $event) {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        self::zendFrameworkOptimization($rootPath);
    }

    public static function postUpdate (Event $event) {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        self::zendFrameworkOptimization($rootPath);
    }

    public static function zendFrameworkOptimization($rootPath) {

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

        foreach($regex as $file) {
            $file = $file[0];

            $excluded = false;
            foreach($excludePatterns as $pattern) {
                if(preg_match("@".$pattern."@", $file)) {
                    $excluded = true;
                    break;
                }
            }

            if(!$excluded) {
                $content = file_get_contents($file);
                $content = preg_replace("@([^/])(require_once)@", "$1//$2", $content);
                file_put_contents($file, $content);
            }
        }
    }
}
