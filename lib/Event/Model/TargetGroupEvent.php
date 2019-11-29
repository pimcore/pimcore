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

namespace Pimcore\Event\Model;

use Pimcore\Model\Tool\Targeting\TargetGroup;
use Symfony\Component\EventDispatcher\Event;

class TargetGroupEvent extends Event
{
    /**
     * @var TargetGroup
     */
    protected $targetGroup;
    protected $arguments;

    /**
     * TargetGroupEvent constructor.
     *
     * @param TargetGroup $targetGroup
     * @param array $arguments
     */
    public function __construct(TargetGroup $targetGroup, array $arguments = [])
    {
        $this->targetGroup = $targetGroup;
        $this->arguments = $arguments;
    }

    /**
     * @return TargetGroup
     */
    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    /**
     * @param TargetGroup $targetGroup
     */
    public function setTargetGroup($targetGroup)
    {
        $this->targetGroup = $targetGroup;
    }

    /**
     * @return TargetGroup
     */
    public function getElement()
    {
        return $this->getTargetGroup();
    }
}
