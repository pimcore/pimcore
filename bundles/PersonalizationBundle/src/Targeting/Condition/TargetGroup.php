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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class TargetGroup extends AbstractVariableCondition implements ConditionInterface
{
    private ?int $targetGroupId = null;

    public function __construct(?int $targetGroupId = null)
    {
        $this->targetGroupId = $targetGroupId;
    }

    /**
     * @return self
     */
    public static function fromConfig(array $config): self
    {
        return new self($config['targetGroup'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return null !== $this->targetGroupId && $this->targetGroupId > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        foreach ($visitorInfo->getAssignedTargetGroups() as $targetGroup) {
            if ($targetGroup->getId() === $this->targetGroupId) {
                $this->setMatchedVariable('target_group_id', $targetGroup->getId());

                return true;
            }
        }

        return false;
    }
}
