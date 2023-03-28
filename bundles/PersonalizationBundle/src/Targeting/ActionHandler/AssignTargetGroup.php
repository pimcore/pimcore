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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\ActionHandler;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\Rule;
use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;

class AssignTargetGroup implements ActionHandlerInterface
{
    const STORAGE_KEY = 'tg';

    public function __construct(
        private TargetingStorageInterface $storage
    ) {
    }

    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null): void
    {
        $targetGroupId = $action['targetGroup'] ?? null;
        if (!$targetGroupId) {
            return;
        }

        $weight = 1;
        if (isset($action['weight'])) {
            $weight = (int)$action['weight'];
            if ($weight < 1) {
                $weight = 1;
            }
        }

        if ($targetGroupId instanceof TargetGroup) {
            $targetGroup = $targetGroupId;
        } else {
            $targetGroup = TargetGroup::getById($targetGroupId);
        }

        if (!$targetGroup || !$targetGroup->getActive()) {
            return;
        }

        $count = $this->storeAssignments($visitorInfo, $targetGroup, $weight);

        $this->assignToVisitor($visitorInfo, $targetGroup, $count);
    }

    public function reset(VisitorInfo $visitorInfo): void
    {
        $success = $this->deleteAssignments($visitorInfo);
    }

    /**
     * Loads stored assignments from storage and applies it to visitor info
     *
     * @param VisitorInfo $visitorInfo
     */
    public function loadStoredAssignments(VisitorInfo $visitorInfo): void
    {
        $data = $this->storage->get(
            $visitorInfo,
            TargetingStorageInterface::SCOPE_VISITOR,
            self::STORAGE_KEY,
            []
        );

        foreach ($data as $targetGroupId => $count) {
            $targetGroup = TargetGroup::getById($targetGroupId);
            if ($targetGroup && $targetGroup->getActive()) {
                $this->assignToVisitor($visitorInfo, $targetGroup, $count);
            }
        }
    }

    protected function storeAssignments(VisitorInfo $visitorInfo, TargetGroup $targetGroup, int $weight): int
    {
        $data = $this->storage->get(
            $visitorInfo,
            TargetingStorageInterface::SCOPE_VISITOR,
            self::STORAGE_KEY,
            []
        );

        $count = $data[$targetGroup->getId()] ?? 0;
        $count += $weight;

        $data[$targetGroup->getId()] = $count;

        $this->storage->set(
            $visitorInfo,
            TargetingStorageInterface::SCOPE_VISITOR,
            self::STORAGE_KEY,
            $data
        );

        return $count;
    }

    protected function deleteAssignments(VisitorInfo $visitorInfo): bool
    {
        $data = [];

        try {
            $this->storage->set(
                $visitorInfo,
                TargetingStorageInterface::SCOPE_VISITOR,
                self::STORAGE_KEY,
                $data
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    protected function assignToVisitor(VisitorInfo $visitorInfo, TargetGroup $targetGroup, int $count): void
    {
        $threshold = $targetGroup->getThreshold();

        // only assign if count reached the threshold if threshold is > 1
        if ($threshold <= 1 || $count >= $threshold) {
            $visitorInfo->assignTargetGroup($targetGroup, $count, true);
        }
    }
}
