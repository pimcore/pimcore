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
use Pimcore\Model\Tool\Targeting\Persona;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\Condition\DataProviderDependentConditionInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;

class TargetGroupResolver
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var DataProviderLocatorInterface
     */
    private $dataProviders;

    /**
     * @var ConditionFactoryInterface
     */
    private $conditionFactory;

    /**
     * @var Rule[]
     */
    private $targetingRules;

    public function __construct(
        DataProviderLocatorInterface $dataProviders,
        ConditionFactoryInterface $conditionFactory,
        Connection $db
    )
    {
        $this->dataProviders    = $dataProviders;
        $this->conditionFactory = $conditionFactory;
        $this->db               = $db;
    }

    public function resolve(Request $request): VisitorInfo
    {
        $visitorInfo = VisitorInfo::fromRequest($request);

        if (!$this->isTargetingConfigured()) {
            return $visitorInfo;
        }

        $this->applyTargetingRules($visitorInfo);
        $this->processTargetingRules($visitorInfo);

        return $visitorInfo;
    }

    private function isTargetingConfigured(): bool
    {
        $configuredRules = $this->db->fetchColumn(
            'SELECT id FROM targeting_personas UNION SELECT id FROM targeting_rules LIMIT 1'
        );

        return $configuredRules && (int)$configuredRules > 0;
    }

    private function processTargetingRules(VisitorInfo $visitorInfo)
    {
        foreach ($visitorInfo->getTargetingRules() as $rule) {
            $this->processTargetingRule($visitorInfo, $rule);
        }
    }

    private function processTargetingRule(VisitorInfo $visitorInfo, Rule $rule)
    {
        $actions = $rule->getActions();
        if (!$actions) {
            return;
        }

        if ($actions->getPersonaEnabled() && $actions->getPersonaId()) {
            $persona = Persona::getById($actions->getPersonaId());

            if ($persona) {
                $visitorInfo->addPersona($persona);
            }
        }
    }

    private function applyTargetingRules(VisitorInfo $visitorInfo)
    {
        $rules = $this->getTargetingRules();

        foreach ($rules as $rule) {
            $this->applyTargetingRule($visitorInfo, $rule);
        }
    }

    private function applyTargetingRule(VisitorInfo $visitorInfo, Rule $rule)
    {
        // TODO handle brackets and logical operations
        // for now everything is just AND-combined

        $match = true;
        foreach ($rule->getConditions() as $conditionConfig) {
            $matchesCondition = $this->matchCondition($visitorInfo, $conditionConfig);
            $match            = $match && $matchesCondition;
        }

        if ($match) {
            $visitorInfo->addTargetingRule($rule);
        }
    }

    private function matchCondition(VisitorInfo $visitorInfo, array $config): bool
    {
        $condition = $this->conditionFactory->build($config);

        // check prerequisites - e.g. a condition without a value
        // (= all values match) does not need to fetch provider data
        // as location or browser
        if (!$condition->canMatch()) {
            return true;
        }

        if ($condition instanceof DataProviderDependentConditionInterface) {
            foreach ($condition->getDataProviderKeys() as $dataProviderKey) {
                $dataProvider = $this->dataProviders->get($dataProviderKey);
                $dataProvider->load($visitorInfo);
            }
        }

        return $condition->match($visitorInfo);
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
