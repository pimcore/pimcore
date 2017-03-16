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

use Pimcore\Loader\ImplementationLoader\Traits\MapLoaderTrait;

/**
 * Loads implementations from a fixed name => className map
 */
class ClassMapLoader extends AbstractClassNameLoader
{
    use MapLoaderTrait;

    /**
     * @param array $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * @param string $name
     * @param string $className
     */
    public function register($name, $className)
    {
        $this->map[$name] = $className;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $name) : bool
    {
        return isset($this->map[$name]);
    }

    /**
     * @inheritDoc
     */
    protected function getClassName($name)
    {
        return $this->map[$name];
    }
}
