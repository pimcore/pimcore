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

class Composer
{
    public static function postCreateProject(Event $event)
    {
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
    }
}
