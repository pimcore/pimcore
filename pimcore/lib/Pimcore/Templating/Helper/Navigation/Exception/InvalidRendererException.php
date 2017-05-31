<?php

declare(strict_types=1);

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

namespace Pimcore\Templating\Helper\Navigation\Exception;

use Pimcore\Navigation\Renderer\RendererInterface;

class InvalidRendererException extends \LogicException
{
    /**
     * @param string $id
     * @param mixed $renderer
     *
     * @return self
     */
    public static function create(string $name, $renderer): self
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
