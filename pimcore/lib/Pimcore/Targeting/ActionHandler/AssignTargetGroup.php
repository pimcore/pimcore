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
use Pimcore\Targeting\Session\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class AssignTargetGroup implements ActionHandlerInterface
{
    /**
     * @var ConditionMatcherInterface
     */
    private $conditionMatcher;

    public function __construct(ConditionMatcherInterface $conditionMatcher)
    {
        $this->conditionMatcher = $conditionMatcher;
    }

    public function apply(VisitorInfo $visitorInfo, Rule $rule, array $action)
    {
        $targetGroupId = $action['targetGroup'] ?? null;
        if (!$targetGroupId) {
            return;
        }

        $targetGroup = TargetGroup::getById($targetGroupId);

        if (!$targetGroup || !$targetGroup->getActive()) {
            return;
        }

        $assign = true;

        // TODO is this appropriate to check for first visit?
        if (!$visitorInfo->hasVisitorId()) {
            $assign = $this->checkEntryConditions($visitorInfo, $targetGroup);
        }

        if (!$assign) {
            return;
        }

        $threshold = (int)$targetGroup->getThreshold();
        if ($threshold > 1) {
            // only check session entries if threshold was configured
            $assign = $this->checkThresholdAssigment($visitorInfo, $targetGroup, $threshold);
        }

        if ($assign) {
            $visitorInfo->addTargetGroup($targetGroup);
        }
    }

    private function checkEntryConditions(VisitorInfo $visitorInfo, TargetGroup $targetGroup): bool
    {
        if (empty($conditions = $targetGroup->getConditions())) {
            return true;
        }

        return $this->conditionMatcher->match($visitorInfo, $conditions);
    }

    private function checkThresholdAssigment(VisitorInfo $visitorInfo, TargetGroup $targetGroup, int $threshold): bool
    {
        $request = $visitorInfo->getRequest();
        if (!$request->getSession()) {
            return false;
        }

        $session = $request->getSession();

        /** @var NamespacedAttributeBag $bag */
        $bag = $session->getBag(SessionConfigurator::TARGETING_BAG);

        $data = $bag->get('assign_target_group', []);

        $assignments = $data[$targetGroup->getId()] ?? 0;
        $assignments++;

        $data[$targetGroup->getId()] = $assignments;
        $bag->set('assign_target_group', $data);

        // check amount after assigning - this means that with
        // a threshold of 3 the target group will be assigned on and
        // after the third matching request
        return $assignments >= $threshold;
    }
}
