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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Loader\ImplementationLoader;

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

/**
 * @internal
 */
abstract class AbstractClassNameLoader implements LoaderInterface, ClassNameLoaderInterface
{
    /**
     * @param string $name
     *
     * @return string
     */
    abstract protected function getClassName(string $name);

    /**
     * {@inheritdoc}
     */
    public function build(string $name, array $params = [])
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        $params = array_values($params);

        $className = $this->getClassName($name);
        $instance = new $className(...$params);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClassName(string $name): bool
    {
        return $this->supports($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNameFor(string $name): string
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        return $this->getClassName($name);
    }
}
