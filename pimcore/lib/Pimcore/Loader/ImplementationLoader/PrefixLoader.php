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

use Doctrine\Common\Inflector\Inflector;
use Pimcore\Tool;

/**
 * Iterates an array of namespace prefixes and tries to load classes by namespace.
 */
class PrefixLoader extends AbstractClassNameLoader
{
    /**
     * @var array
     */
    protected $prefixes = [];

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @param array $prefixes
     */
    public function __construct(array $prefixes = [])
    {
        $this->prefixes = $prefixes;
    }

    /**
     * @param string $prefix
     */
    public function addPrefix($prefix)
    {
        $this->prefixes[] = $prefix;
        $this->prefixes = array_unique($this->prefixes);
    }

    /**
     * @inheritDoc
     */
    public function supports(string $name) : bool
    {
        return null !== $this->findClassName($name);
    }

    /**
     * @inheritDoc
     */
    protected function getClassName($name)
    {
        return $this->findClassName($name);
    }

    /**
     * Iterates prefixes and tries to find the class name
     *
     * @param $name
     *
     * @return mixed|string
     */
    protected function findClassName($name)
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $result = null;
        foreach ($this->prefixes as $prefix) {
            $className = $this->buildClassName($prefix, $name);

            if (Tool::classExists($className)) {
                $this->cache[$name] = $className;

                return $className;
            }
        }
    }

    /**
     * @param string $prefix
     * @param string $name
     *
     * @return string
     */
    protected function buildClassName($prefix, $name)
    {
        return $prefix . $this->normalizeName($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        return Inflector::classify($name);
    }
}
