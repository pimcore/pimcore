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

namespace Pimcore\Loader\ImplementationLoader;

/**
 * Loads implementations from a fixed name => className map
 */
interface ClassMapLoaderInterface
{
    /**
     * Add an entry to the classmap
     *
     * @param string $name
     * @param string $className
     */
    public function addClassMap(string $name, string $className);

    /**
     * @return array
     */
    public function getClassMap(): array;

    /**
     * @param array $classMap
     */
    public function setClassMap(array $classMap);
}
