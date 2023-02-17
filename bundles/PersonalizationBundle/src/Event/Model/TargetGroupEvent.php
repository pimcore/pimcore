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

namespace Pimcore\Bundle\PersonalizationBundle\Event\Model;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Symfony\Contracts\EventDispatcher\Event;

class TargetGroupEvent extends Event
{
    protected TargetGroup $targetGroup;

    protected array $arguments;

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

    public function getTargetGroup(): TargetGroup
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(TargetGroup $targetGroup): void
    {
        $this->targetGroup = $targetGroup;
    }

    public function getElement(): TargetGroup
    {
        return $this->getTargetGroup();
    }
}
