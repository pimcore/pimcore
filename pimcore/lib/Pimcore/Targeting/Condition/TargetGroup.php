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

namespace Pimcore\Targeting\Condition;

use Pimcore\Targeting\Model\VisitorInfo;

class TargetGroup extends AbstractVariableCondition implements ConditionInterface
{
    /**
     * @var int|null
     */
    private $targetGroupId;

    /**
     * @param int|null $targetGroupId
     */
    public function __construct(int $targetGroupId = null)
    {
        $this->targetGroupId = $targetGroupId;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new self($config['targetGroup'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return null !== $this->targetGroupId && $this->targetGroupId > 0;
    }

    /**
     * @inheritDoc
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
