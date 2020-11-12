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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Placeholder.php
/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Placeholder
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Twig\Extension\Templating\Placeholder\AbstractExtension;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;

/**
 * Helper for passing data between otherwise segregated Views. It's called
 * Placeholder to make its typical usage obvious, but can be used just as easily
 * for non-Placeholder things. That said, the support for this is only
 * guaranteed to effect subsequently rendered templates, and of course Layouts.
 *
 */
class Placeholder extends AbstractExtension
{
    /**
     * Registry key under which container registers itself
     *
     * @var string
     */
    protected $_regKey = 'Placeholder';

    /**
     * @var Container[]
     */
    protected $containers = [];

========
namespace Pimcore\Templating\Helper;

@trigger_error(
    'Pimcore\Templating\Helper\Placeholder is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Placeholder::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Placeholder::class);

if (false) {
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Placeholder.php
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Placeholder
     */
    class Placeholder extends \Pimcore\Twig\Extension\Templating\Placeholder {

    }
}
