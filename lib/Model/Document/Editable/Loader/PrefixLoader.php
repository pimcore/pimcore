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

namespace Pimcore\Model\Document\Editable\Loader;

use Pimcore\Loader\ImplementationLoader\PrefixLoader as BasePrefixLoader;

/**
 * @internal
 */
final class PrefixLoader extends BasePrefixLoader
{
    protected function normalizeName(string $name): string
    {
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }
}
