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

namespace Pimcore\Model\Object\ClassDefinition\Loader;

use Pimcore\Loader\ImplementationLoader\ImplementationLoader;
use Pimcore\Model\Object\ClassDefinition\Layout;

class LayoutLoader extends ImplementationLoader implements LayoutLoaderInterface
{
    /**
     * @inheritDoc
     */
    protected function init()
    {
        $normalizer = function ($name) {
            return ucfirst($name);
        };

        $this->prefixLoader->addPrefix('\\Pimcore\\Model\\Object\\ClassDefinition\\Layout\\', $normalizer);
        $this->prefixLoader->addPrefix('\\Object_Class_Layout', $normalizer);
    }

    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = []): Layout
    {
        return parent::build($name, $params);
    }
}
