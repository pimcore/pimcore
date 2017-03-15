<?php
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

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

class DelegatingLoader implements LoaderInterface
{
    /**
     * @var LoaderInterface[]
     */
    protected $loaders;

    /**
     * @var LoaderInterface[]
     */
    protected $sorted;

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        foreach ($loaders as $loader) {
            $this->register($loader);
        }
    }

    /**
     * @param LoaderInterface $loader
     * @param int $priority
     */
    public function register(LoaderInterface $loader, $priority = 0)
    {
        if (!isset($this->loaders[$priority])) {
            $this->loaders[$priority] = [];
        }

        $this->loaders[$priority][] = $loader;
        $this->sorted = null;
    }

    /**
     * @return LoaderInterface[]
     */
    protected function getSortedLoaders()
    {
        if (null === $this->sorted) {
            krsort($this->loaders);
            $this->sorted = call_user_func_array('array_merge', $this->loaders);
        }

        return $this->sorted;
    }

    /**
     * @param string $name
     *
     * @return LoaderInterface
     */
    protected function getLoader($name)
    {
        foreach ($this->getSortedLoaders() as $loader) {
            if ($loader->supports($name)) {
                return $loader;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function supports($name)
    {
        return null !== $this->getLoader($name);
    }

    /**
     * @inheritDoc
     */
    public function build($name, array $params = [])
    {
        $loader = $this->getLoader($name);
        if (null === $loader) {
            throw new UnsupportedException(sprintf('Loader for "%s" was not found', $name));
        }

        return $loader->build($name, $params);
    }
}
