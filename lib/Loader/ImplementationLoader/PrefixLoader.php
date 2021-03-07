<?php

declare(strict_types = 1);

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

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Pimcore\Loader\ImplementationLoader\Exception\InvalidArgumentException;
use Pimcore\Tool;

/**
 * Iterates an array of namespace prefixes and tries to load classes by namespace.
 */
class PrefixLoader extends AbstractClassNameLoader
{
    /**
     * @var Inflector
     */
    private $inflector;

    /**
     * @var array
     */
    private $prefixes = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param array $prefixes
     */
    public function __construct(array $prefixes = [])
    {
        $this->inflector = InflectorFactory::create()->build();
        $this->setPrefixes($prefixes);
    }

    /**
     * @inheritDoc
     */
    private function setPrefixes(array $prefixes)
    {
        if (empty($prefixes)) {
            throw new InvalidArgumentException('Prefix loader needs a list of prefixes, empty array given');
        }

        $this->prefixes = [];
        foreach ($prefixes as $prefix) {
            if (!is_string($prefix)) {
                throw new InvalidArgumentException(sprintf('Prefix must be a string, %s given', gettype($prefix)));
            }

            $this->prefixes[] = $prefix;
        }

        $this->prefixes = array_unique($this->prefixes);
    }

    /**
     * @inheritDoc
     */
    public function supports(string $name): bool
    {
        return null !== $this->findClassName($name);
    }

    /**
     * @inheritDoc
     */
    protected function getClassName(string $name)
    {
        return $this->findClassName($name);
    }

    /**
     * Iterates prefixes and tries to find the class name
     *
     * @param string $name
     *
     * @return mixed|string
     */
    private function findClassName(string $name)
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
    protected function buildClassName(string $prefix, string $name): string
    {
        return $prefix . $this->normalizeName($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName(string $name): string
    {
        return $this->inflector->classify($name);
    }
}
