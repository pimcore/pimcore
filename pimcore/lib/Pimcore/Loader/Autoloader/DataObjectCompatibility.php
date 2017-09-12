<?php

declare(strict_types=1);

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

namespace Pimcore\Loader\Autoloader;

class DataObjectCompatibility extends AbstractAutoloader
{
    public function load($class)
    {
        if (strpos($class, 'Pimcore\\') === 0 && strpos($class, '\\Object\\')) {
            $realClassName = str_replace('\\Object\\', '\\DataObject\\', $class);
            if (!$this->classExists($realClassName, false)) {
                $this->composerAutoloader->loadClass($realClassName);
            }

            if ($this->classExists($realClassName, false)) {
                class_alias($realClassName, $class);

                return true;
            }
        } elseif (strpos($class, 'Pimcore\\') === 0 && strpos($class, '\\DataObject\\')) {
            if ($this->composerAutoloader->loadClass($class)) {
                $oldClassName = str_replace('\\DataObject\\', '\\Object\\', $class);
                class_alias($class, $oldClassName, false);

                return true;
            }
        }

        return false;
    }

    public function register(bool $prepend = false)
    {
        spl_autoload_register([$this, 'load'], true, $prepend);
    }

    public function unregister()
    {
        spl_autoload_unregister([$this, 'load']);
    }
}
