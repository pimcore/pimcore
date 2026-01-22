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

use LogicException;
use Pimcore\Navigation\Renderer\RendererInterface;

class InvalidRendererException extends LogicException
{
    public static function create(string $name, mixed $renderer): static
    {
        $type = is_object($renderer) ? get_class($renderer) : gettype($renderer);

        return new static(sprintf(
            'Renderer for name "%s" was expected to implement interface "%s", "%s" given.',
            $name,
            RendererInterface::class,
            $type
        ));
    }
}
