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

namespace Pimcore\Model\Asset\Metadata\Loader;

use Pimcore\Loader\ImplementationLoader\ImplementationLoader;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\DataDefinitionInterface;

final class DataLoader extends ImplementationLoader implements DataLoaderInterface
{
    public function build(string $name, array $params = []): DataDefinitionInterface
    {
        return parent::build($name, $params);
    }
}
