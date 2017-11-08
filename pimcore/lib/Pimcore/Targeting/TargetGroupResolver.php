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

namespace Pimcore\Targeting;

use Doctrine\DBAL\Connection;
use Pimcore\Event\Targeting\BuildVisitorInfoEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class TargetGroupResolver
{
    const ATTRIBUTE_VISITOR_INFO = '_visitor_info';

    /**
     * @var ConditionMatcherInterface
     */
    private $conditionMatcher;

    /**
     * @var ActionHandlerLocatorInterface
     */
    private $actionHandlers;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Rule[]
     */
    private $targetingRules;

    public function __construct(
        ConditionMatcherInterface $conditionMatcher,
        ActionHandlerLocatorInterface $actionHandlers,
        Connection $db,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->conditionMatcher = $conditionMatcher;
        $this->actionHandlers   = $actionHandlers;
        $this->eventDispatcher  = $eventDispatcher;
        $this->db               = $db;
    }

    public function resolve(Request $request): VisitorInfo
    {
        if ($request->attributes->has(self::ATTRIBUTE_VISITOR_INFO)) {
            return $request->attributes->get(self::ATTRIBUTE_VISITOR_INFO);
        }

        $visitorInfo = $this->createVisitorInfo($request);

        if (!$this->isTargetingConfigured()) {
            return $visitorInfo;
        }

        $this->matchTargetingRuleConditions($visitorInfo);
        $this->handleTargetingRuleActions($visitorInfo);

        $request->attributes->set(self::ATTRIBUTE_VISITOR_INFO, $visitorInfo);

        return $visitorInfo;
    }

    private function createVisitorInfo(Request $request)
    {
        $visitorInfo = VisitorInfo::fromRequest($request);

        $this->eventDispatcher->dispatch(
            TargetingEvents::BUILD_VISITOR_INFO,
            new BuildVisitorInfoEvent($visitorInfo)
        );

        return $visitorInfo;
    }

    public function isTargetingConfigured(): bool
    {
        $configuredRules = $this->db->fetchColumn(
            'SELECT id FROM targeting_personas UNION SELECT id FROM targeting_rules LIMIT 1'
        );

        return $configuredRules && (int)$configuredRules > 0;
    }

    private function matchTargetingRuleConditions(VisitorInfo $visitorInfo)
    {
        $rules = $this->getTargetingRules();

        foreach ($rules as $rule) {
            $this->matchTargetingRuleCondition($visitorInfo, $rule);
        }
    }

    private function matchTargetingRuleCondition(VisitorInfo $visitorInfo, Rule $rule)
    {
        $match = $this->conditionMatcher->match(
            $visitorInfo,
            $rule->getConditions()
        );

        if ($match) {
            $visitorInfo->addTargetingRule($rule);
        }
    }

    private function handleTargetingRuleActions(VisitorInfo $visitorInfo)
    {
        foreach ($visitorInfo->getTargetingRules() as $rule) {
            $actions = $rule->getActions();
            if (!$actions || !is_array($actions)) {
                continue;
            }

            foreach ($actions as $action) {
                if (!is_array($action)) {
                    continue;
                }

                $this->handleTargetingRuleAction($visitorInfo, $rule, $action);
            }
        }
    }

    private function handleTargetingRuleAction(VisitorInfo $visitorInfo, Rule $rule, array $action)
    {
        /** @var string $type */
        $type = $action['type'] ?? null;

        if (empty($type)) {
            throw new \InvalidArgumentException('Invalid action: Type is not set');
        }

        if (!$this->actionHandlers->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid condition: there is no action handler registered for type "%s"',
                $type
            ));
        }

        $actionHandler = $this->actionHandlers->get($type);
        $actionHandler->apply($visitorInfo, $rule, $action);
    }

    /**
     * @return Rule[]
     */
    private function getTargetingRules(): array
    {
        if (null !== $this->targetingRules) {
            return $this->targetingRules;
        }

        /** @var Rule\Listing|Rule\Listing\Dao $list */
        $list = new Rule\Listing();
        $list->setCondition('active = 1');

        $this->targetingRules = $list->load();

        return $this->targetingRules;
    }
}
