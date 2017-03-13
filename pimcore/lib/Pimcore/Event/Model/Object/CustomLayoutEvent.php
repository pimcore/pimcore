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

namespace Pimcore\Event\Model\Object;

use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\EventDispatcher\Event;

class CustomLayoutEvent extends Event
{

    /**
     * @var ClassDefinition\CustomLayout
     */
    protected $customLayout;

    /**
     * DocumentEvent constructor.
     * @param ClassDefinition\CustomLayout $customLayout
     */
    public function __construct(ClassDefinition\CustomLayout $customLayout)
    {
        $this->customLayout = $customLayout;
    }

    /**
     * @return ClassDefinition\CustomLayout
     */
    public function getCustomLayout()
    {
        return $this->customLayout;
    }

    /**
     * @param ClassDefinition\CustomLayout $customLayout
     */
    public function setCustomLayout($customLayout)
    {
        $this->customLayout = $customLayout;
    }
}
