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

@trigger_error(
    'Pimcore\Templating\Helper\Navigation\Exception\InvalidRendererException is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException
     */
    class InvalidRendererException extends \Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException {

    }
}


