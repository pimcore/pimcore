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

use Composer\Autoload\ClassLoader;

abstract class AbstractAutoloader
{
    /**
     * @var ClassLoader
     */
    protected $composerAutoloader;

    public function __construct(ClassLoader $composerAutoloader)
    {
        $this->composerAutoloader = $composerAutoloader;
    }

    public function classExists(string $class, bool $autoload = true): bool
    {
        return class_exists($class, $autoload) || interface_exists($class, $autoload) || trait_exists($class, $autoload);
    }
}
