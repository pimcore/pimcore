<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Templating\Helper\Traits;

@trigger_error(
    'Pimcore\Templating\Helper\Traits\TextUtilsTrait is deprecated since version 6.8.0 and will be removed in Pimcore 10. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Traits\TextUtilsTrait::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Traits\TextUtilsTrait::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Traits\TextUtilsTrait
     */
    trait TextUtilsTrait
    {
        use \Pimcore\Twig\Extension\Templating\Traits\TextUtilsTrait;
    }
}
