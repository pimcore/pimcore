<?php

/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore;

use Composer\Script\Event;
use Composer\Util\Filesystem;

class Composer
{
    public static function postCreateProject(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        // cleanup
        @unlink($rootPath . '/.scrutinizer.yml');
        @unlink($rootPath . '/.travis.yml');

        rename($rootPath . '/plugins_example', $rootPath . '/plugins');
        rename($rootPath . '/website_example', $rootPath . '/website');

        $filesystem = new Filesystem();
        $filesystem->removeDirectory($rootPath . '/update');
        $filesystem->removeDirectory($rootPath . '/build');
        $filesystem->removeDirectory($rootPath . '/tests');
        $filesystem->removeDirectory($rootPath . '/.svn');
    }
}
