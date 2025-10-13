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

namespace Pimcore\Model\Factory;

use Pimcore\Loader\ImplementationLoader\AbstractClassNameLoader;

/**
 * @internal
 */
final class FallbackBuilder extends AbstractClassNameLoader
{
    public function supports(string $name): bool
    {
        return class_exists($name);
    }

    protected function getClassName(string $name): string
    {
        return $name;
    }
}
