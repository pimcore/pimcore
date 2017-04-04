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

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

abstract class AbstractClassNameLoader implements LoaderInterface
{
    /**
     * @param string $name
     *
     * @return string
     */
    abstract protected function getClassName(string $name);

    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = [])
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        $params = array_values($params);

        $className = $this->getClassName($name);
        $instance  = new $className(...$params);

        return $instance;
    }
}
