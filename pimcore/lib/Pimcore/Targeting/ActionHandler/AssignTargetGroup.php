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

namespace Pimcore\Targeting\ActionHandler;

use Pimcore\Model\Tool\Targeting\Persona as TargetGroup;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\ConditionMatcherInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Storage\TargetingStorageInterface;

class AssignTargetGroup implements ActionHandlerInterface
{
    /**
     * @var ConditionMatcherInterface
     */
    private $conditionMatcher;

    /**
     * @var TargetingStorageInterface
     */
    private $storage;

    public function __construct(
        ConditionMatcherInterface $conditionMatcher,
        TargetingStorageInterface $storage
    )
    {
        $this->conditionMatcher = $conditionMatcher;
        $this->storage          = $storage;
    }

    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
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

        $targetGroup = TargetGroup::getById($targetGroupId);

        if (!$targetGroup || !$targetGroup->getActive()) {
            return;
        }

        $assign = true;

        if (!$assign) {
            return;
        }

        $assignments = $this->storeAssignments($visitorInfo, $targetGroup, $weight);

        $threshold = (int)$targetGroup->getThreshold();
        if ($threshold > 1 && $assignments < $threshold) {
            $assign = false;
        }

        if ($assign) {
            $visitorInfo->assignTargetGroup($targetGroup, $assignments);
        }
    }

    private function storeAssignments(VisitorInfo $visitorInfo, TargetGroup $targetGroup, int $weight): int
    {
        $storageKey = 'assign_target_group';

        $data = $this->storage->get($visitorInfo, $storageKey, []);

        $assignments = $data[$targetGroup->getId()] ?? 0;
        $assignments += $weight;

        $data[$targetGroup->getId()] = $assignments;

        $this->storage->set($visitorInfo, $storageKey, $data);

        return $assignments;
    }
}
