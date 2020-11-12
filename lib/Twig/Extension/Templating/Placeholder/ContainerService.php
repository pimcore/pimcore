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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Placeholder/ContainerService.php
/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Placeholder_Registry
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

namespace Pimcore\Twig\Extension\Templating\Placeholder;

/**
 * Registry for placeholder containers
 *
 */
class ContainerService
{
    /**
     * @var int
     */
    private $currentIndex = 0;

    /**
     * Placeholder containers
     *
     * @var array
     */
    protected $_items = [];

    public function __construct()
    {
        $this->_items[$this->currentIndex] = [];
    }

    public function pushIndex()
    {
        ++$this->currentIndex;

        if (isset($this->_items[$this->currentIndex])) {
            throw new \RuntimeException(sprintf('Items at index %d already exist', $this->currentIndex));
        }

        $this->_items[$this->currentIndex] = [];
    }

    public function popIndex()
    {
        if (0 === $this->currentIndex) {
            throw new \OutOfBoundsException('Current index is already at 0');
        }
========
namespace Pimcore\Templating\Helper\Placeholder;

@trigger_error(
    'Pimcore\Templating\Helper\Placeholder\ContainerService is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Placeholder\ContainerService::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Placeholder/ContainerService.php

class_exists(\Pimcore\Twig\Extension\Templating\Placeholder\ContainerService::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Placeholder\ContainerService
     */
    class ContainerService extends \Pimcore\Twig\Extension\Templating\Placeholder\ContainerService {

    }
}
