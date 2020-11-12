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

namespace Pimcore\Templating\Helper;

@trigger_error(
    'Pimcore\Templating\Helper\InlineScript is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\InlineScript::class . ' instead.',
    E_USER_DEPRECATED
);

<<<<<<<< HEAD:lib/Twig/Extension/Templating/InlineScript.php
namespace Pimcore\Twig\Extension\Templating;

use Twig\Extension\RuntimeExtensionInterface;

class InlineScript extends HeadScript implements RuntimeExtensionInterface
{
========
class_exists(\Pimcore\Twig\Extension\Templating\InlineScript::class);

if (false) {
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/InlineScript.php
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\InlineScript
     */
    class InlineScript extends \Pimcore\Twig\Extension\Templating\InlineScript {

    }
}
