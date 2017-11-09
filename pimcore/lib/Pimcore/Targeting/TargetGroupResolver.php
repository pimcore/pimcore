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
use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\Targeting\TargetingRuleEvent;
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
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

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
        TargetingStorageInterface $targetingStorage,
        ConditionMatcherInterface $conditionMatcher,
        ActionHandlerLocatorInterface $actionHandlers,
        Connection $db,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->targetingStorage = $targetingStorage;
        $this->conditionMatcher = $conditionMatcher;
        $this->actionHandlers   = $actionHandlers;
        $this->eventDispatcher  = $eventDispatcher;
        $this->db               = $db;
    }

    public function resolve(Request $request): VisitorInfo
    {
        if ($this->targetingStorage->hasVisitorInfo()) {
            return $this->targetingStorage->getVisitorInfo();
        }

        $visitorInfo = VisitorInfo::fromRequest($request);

        if (!$this->isTargetingConfigured()) {
            return $visitorInfo;
        }

        $this->eventDispatcher->dispatch(
            TargetingEvents::PRE_RESOLVE,
            new TargetingEvent($visitorInfo)
        );

        $this->matchTargetingRuleConditions($visitorInfo);

        $this->eventDispatcher->dispatch(
            TargetingEvents::POST_RESOLVE,
            new TargetingEvent($visitorInfo)
        );

        $this->targetingStorage->setVisitorInfo($visitorInfo);

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

        if (!$match) {
            return;
        }

        // store info about matched rule
        $visitorInfo->addTargetingRule($rule);

        $this->eventDispatcher->dispatch(
            TargetingEvents::PRE_RULE_ACTIONS,
            new TargetingRuleEvent($visitorInfo, $rule)
        );

        // execute rule actions
        $this->handleTargetingRuleActions($visitorInfo, $rule);

        $this->eventDispatcher->dispatch(
            TargetingEvents::POST_RULE_ACTIONS,
            new TargetingRuleEvent($visitorInfo, $rule)
        );
    }

    private function handleTargetingRuleActions(VisitorInfo $visitorInfo, Rule $rule)
    {
        $actions = $rule->getActions();
        if (!$actions || !is_array($actions)) {
            return;
        }

        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $this->applyAction($visitorInfo, $action, $rule);
        }
    }

    /**
     * Applies a raw action from config
     *
     * @param VisitorInfo $visitorInfo
     * @param array $action
     * @param Rule|null $rule
     */
    public function applyAction(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
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
        $actionHandler->apply($visitorInfo, $action, $rule);
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
        $list->setOrderKey('prio');
        $list->setOrder('ASC');

        $this->targetingRules = $list->load();

        return $this->targetingRules;
    }
}
