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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup as ToolTargetGroup;

class TargetGroup implements ConditionInterface
{
    protected ?int $targetGroupId = null;

    protected ?ToolTargetGroup $targetGroup = null;

    protected int $threshold = 0;

    public function check(EnvironmentInterface $environment): bool
    {
        $visitorInfo = $environment->getVisitorInfo();

        if ($visitorInfo) {
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
     *
     * @internal
     */
    public function __sleep(): array
    {
        return ['targetGroupId', 'threshold'];
    }

    /**
     * @internal
     */
    public function __wakeup(): void
    {
        if ($this->targetGroupId) {
            $this->targetGroup = ToolTargetGroup::getById($this->targetGroupId);
        }
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'TargetGroup', 'targetGroupId' => $this->targetGroupId, 'threshold' => (int) $this->threshold,
        ];

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        if ($json->targetGroupId) {
            $this->setTargetGroupId($json->targetGroupId);
        }
        if ($json->threshold) {
            $this->setThreshold((int) $json->threshold);
        }

        return $this;
    }

    public function getTargetGroupId(): int
    {
        return $this->targetGroupId;
    }

    public function setTargetGroupId(int $targetGroupId): void
    {
        $this->targetGroupId = $targetGroupId;
        if ($this->targetGroupId) {
            $this->targetGroup = ToolTargetGroup::getById($this->targetGroupId);
        } else {
            $this->targetGroup = null;
        }
    }

    public function getTargetGroup(): ToolTargetGroup
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(ToolTargetGroup $targetGroup): void
    {
        $this->targetGroup = $targetGroup;
        $this->targetGroupId = $targetGroup->getId();
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }
}
