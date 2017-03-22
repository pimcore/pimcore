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

use Doctrine\Common\Inflector\Inflector;
use Pimcore\Tool;

/**
 * Iterates an array of namespace prefixes and tries to load classes by namespace.
 */
class PrefixLoader extends AbstractClassNameLoader implements PrefixLoaderInterface
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
        foreach ($prefixes as $prefix) {
            $this->addPrefix($prefix);
        }
    }

    /**
     * @inheritdoc
     */
    public function addPrefix(string $prefix, callable $normalizer = null)
    {
        $this->prefixes[$prefix] = [$prefix, $normalizer];
    }

    /**
     * @inheritDoc
     */
    public function addPrefixes(array $prefixes, callable $normalizer = null)
    {
        foreach ($prefixes as $prefix) {
            $this->addPrefix($prefix, $normalizer);
        }
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
     * @param $name
     *
     * @return mixed|string
     */
    protected function findClassName(string $name)
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
     * @param array $prefix
     * @param string $name
     *
     * @return string
     */
    protected function buildClassName(array $prefix, $name): string
    {
        return $prefix[0] . $this->normalizeName($name, $prefix[1]);
    }

    /**
     * @param string $name
     * @param callable $normalizer
     *
     * @return string
     */
    protected function normalizeName(string $name, callable $normalizer = null): string
    {
        if (null === $normalizer) {
            $normalizer = [Inflector::class, 'classify'];
        }

        return call_user_func($normalizer, $name);
    }
}
