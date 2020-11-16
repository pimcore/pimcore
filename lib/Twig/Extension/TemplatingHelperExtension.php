<?php
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

namespace Pimcore\Twig\Extension;

@trigger_error(
    sprintf(
        'Class "%s" is deprecated since v6.9 and will be removed in 7. Use one of these "%s", "%s", "%s", "%s" instead.',
        TemplatingHelperExtension::class,
        HeaderExtension::class,
        PimcoreToolExtension::class,
        HelpersExtension::class,
        CacheExtension::class
    ),
    E_USER_DEPRECATED
);

class_exists(HeaderExtension::class);
class_exists(CacheExtension::class);
class_exists(PimcoreToolExtension::class);
class_exists(HelpersExtension::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Twig\Extension\HeaderExtension instead.
     * @deprecated use \Pimcore\Twig\Extension\PimcoreToolExtension instead.
     * @deprecated use \Pimcore\Twig\Extension\HelpersExtension instead.
     * @deprecated use \Pimcore\Twig\Extension\CacheExtension instead.
     */
    class TemplatingHelperExtension
    {
    }
}
