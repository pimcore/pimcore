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

namespace Pimcore\Twig\Extension\Templating\Placeholder;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Placeholder/CacheBusterAware.php
/**
 * Class CacheBusterAware
 *
 * adds cache buster functionality to placeholder extension
 */
abstract class CacheBusterAware extends AbstractExtension
{
    /**
     * @var bool
     */
    protected $cacheBuster = true;
========
@trigger_error(
    'Pimcore\Templating\Helper\Placeholder\CacheBusterAware is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Placeholder/CacheBusterAware.php

class_exists(\Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware
     */
    abstract class CacheBusterAware extends \Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware {

    }
}
