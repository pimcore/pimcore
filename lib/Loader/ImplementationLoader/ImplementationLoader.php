<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Loader\ImplementationLoader;

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

/**
 * Core implementation loader delegating to a list of registered loaders
 *
 * @internal
 */
class ImplementationLoader implements LoaderInterface, ClassNameLoaderInterface
{
    /**
     * @var LoaderInterface[]
     */
    protected array $loaders;

    private array $loaderCache = [];

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        $this->setLoaders($loaders);
    }

    /**
     * @param LoaderInterface[] $loaders
     */
    private function setLoaders(array $loaders): void
    {
        $this->loaders = [];
        $this->loaderCache = [];

        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    public function addLoader(LoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }

    private function getLoader(string $name): ?LoaderInterface
    {
        // loader cache contains index of loader previously found for given name
        if (isset($this->loaderCache[$name])) {
            return $this->loaders[$this->loaderCache[$name]];
        }

        /** @var LoaderInterface $loader */
        foreach (array_reverse($this->loaders, true) as $idx => $loader) {
            if ($loader->supports($name)) {
                $this->loaderCache[$name] = $idx;

                return $loader;
            }
        }

        return null;
    }

    public function supports(string $name): bool
    {
        return null !== $this->getLoader($name);
    }

    public function build(string $name, array $params = []): mixed
    {
        $loader = $this->getLoader($name);
        if (null === $loader) {
            throw new UnsupportedException(sprintf('Loader for "%s" was not found', $name));
        }

        return $loader->build($name, $params);
    }

    public function supportsClassName(string $name): bool
    {
        $loader = $this->getLoader($name);

        if (null === $loader || !$loader instanceof ClassNameLoaderInterface) {
            return false;
        }

        return $loader->supportsClassName($name);
    }

    public function getClassNameFor(string $name): string
    {
        $loader = $this->getLoader($name);
        if (null === $loader) {
            throw new UnsupportedException(sprintf('Loader for "%s" was not found', $name));
        }

        if (!$loader instanceof ClassNameLoaderInterface) {
            throw new UnsupportedException(sprintf(
                'Loader "%s" for "%s" does not support building a class name',
                get_class($loader),
                $name
            ));
        }

        if (!$loader->supportsClassName($name)) {
            throw new UnsupportedException(sprintf(
                'Building a class name for "%s" from loader "%s" is not supported',
                $name,
                get_class($loader)
            ));
        }

        return $loader->getClassNameFor($name);
    }
}
