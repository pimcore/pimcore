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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class TargetGroup implements ICondition
{
    /**
     * @var int[]
     */
    protected $targetGroupId;

    /**
     * @var \Pimcore\Model\Tool\Targeting\TargetGroup
     */
    protected $targetGroup;

    /**
     * @var int
     */
    protected $threshold;

    /**
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        $visitorInfo = $environment->getVisitorInfo();

        if ($this->getTargetGroup()) {
            if ($visitorInfo->hasTargetGroupAssignment($this->getTargetGroup())) {
                if ($visitorInfo->getTargetGroupAssignment($this->getTargetGroup())->getCount() > $this->getThreshold()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['targetGroupId', 'threshold'];
    }

    public function __wakeup()
    {
        if ($this->targetGroupId) {
            $this->targetGroup = \Pimcore\Model\Tool\Targeting\TargetGroup::getById($this->targetGroupId);
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'TargetGroup', 'targetGroupId' => $this->targetGroupId, 'threshold' => $this->threshold
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        if ($json->targetGroupId) {
            $this->setTargetGroupId($json->targetGroupId);
        }
        if ($json->threshold) {
            $this->setThreshold($json->threshold);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTargetGroupId(): array
    {
        return $this->targetGroupId;
    }

    /**
     * @param int $targetGroupId
     */
    public function setTargetGroupId(int $targetGroupId)
    {
        $this->targetGroupId = $targetGroupId;
        if ($this->targetGroupId) {
            $this->targetGroup = \Pimcore\Model\Tool\Targeting\TargetGroup::getById($this->targetGroupId);
        } else {
            $this->targetGroup = null;
        }
    }

    /**
     * @return \Pimcore\Model\Tool\Targeting\TargetGroup
     */
    public function getTargetGroup(): \Pimcore\Model\Tool\Targeting\TargetGroup
    {
        return $this->targetGroup;
    }

    /**
     * @param \Pimcore\Model\Tool\Targeting\TargetGroup $targetGroup
     */
    public function setTargetGroup(\Pimcore\Model\Tool\Targeting\TargetGroup $targetGroup)
    {
        $this->targetGroup = $targetGroup;
        if ($this->targetGroup) {
            $this->targetGroupId = $targetGroup->getId();
        } else {
            $this->targetGroupId = null;
        }
    }

    /**
     * @return int
     */
    public function getThreshold(): int
    {
        return $this->threshold;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold(int $threshold)
    {
        $this->threshold = $threshold;
    }
}
