<?php

declare(strict_types = 1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Loader\ImplementationLoader;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Pimcore\Loader\ImplementationLoader\Exception\InvalidArgumentException;
use Pimcore\Tool;

/**
 * Iterates an array of namespace prefixes and tries to load classes by namespace.
 *
 * @internal
 */
class PrefixLoader extends AbstractClassNameLoader
{
    private Inflector $inflector;

    private array $prefixes = [];

    private array $cache = [];

    public function __construct(array $prefixes = [])
    {
        $this->inflector = InflectorFactory::create()->build();
        $this->setPrefixes($prefixes);
    }

    private function setPrefixes(array $prefixes): void
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

    public function supports(string $name): bool
    {
        return null !== $this->findClassName($name);
    }

    protected function getClassName(string $name): string
    {
        return $this->findClassName($name);
    }

    /**
     * Iterates prefixes and tries to find the class name
     */
    private function findClassName(string $name): ?string
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        foreach ($this->prefixes as $prefix) {
            $className = $this->buildClassName($prefix, $name);

            if (Tool::classExists($className)) {
                $this->cache[$name] = $className;

                return $className;
            }
        }

        return null;
    }

    protected function buildClassName(string $prefix, string $name): string
    {
        return $prefix . $this->normalizeName($name);
    }

    protected function normalizeName(string $name): string
    {
        return $this->inflector->classify($name);
    }
}
