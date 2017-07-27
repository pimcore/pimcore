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

namespace Pimcore\Model;

use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Loader\ImplementationLoader\ImplementationLoader;

class Factory extends ImplementationLoader
{
    /**
     * Get class map from all classmap loaders
     */
    public function getClassMap(): array
    {
        $map = [];
        foreach ($this->loaders as $loader) {
            if ($loader instanceof ClassMapLoader) {
                $map = array_merge($map, $loader->getClassMap());
            }
        }

        return $map;
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @return AbstractModel
     */
    public function build(string $name, array $params = []): AbstractModel
    {
        return parent::build($name, $params);
    }
}
