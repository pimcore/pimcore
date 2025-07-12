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
 * @internal
 */
abstract class AbstractClassNameLoader implements LoaderInterface, ClassNameLoaderInterface
{
    abstract protected function getClassName(string $name): string;

    public function build(string $name, array $params = []): mixed
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        $params = array_values($params);

        $className = $this->getClassName($name);
        $instance = new $className(...$params);

        return $instance;
    }

    public function supportsClassName(string $name): bool
    {
        return $this->supports($name);
    }

    public function getClassNameFor(string $name): string
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        return $this->getClassName($name);
    }
}
