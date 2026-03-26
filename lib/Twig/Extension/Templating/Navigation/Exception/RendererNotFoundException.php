<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Twig\Extension\Templating\Navigation\Exception;

use InvalidArgumentException;

class RendererNotFoundException extends InvalidArgumentException
{
    public static function create(string $name): static
    {
        return new static(sprintf('The navigation renderer "%s" was not found', $name));
    }
}
