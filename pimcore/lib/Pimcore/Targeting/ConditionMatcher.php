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

use Pimcore\Targeting\Condition\DataProviderDependentConditionInterface;
use Pimcore\Targeting\ConditionMatcher\Expression\Closure;
use Pimcore\Targeting\ConditionMatcher\Operator\Boolean;
use Pimcore\Targeting\ConditionMatcher\RuleBuilder;
use Pimcore\Targeting\Model\VisitorInfo;

class ConditionMatcher implements ConditionMatcherInterface
{
    /**
     * @var DataProviderLocatorInterface
     */
    private $dataProviders;

    /**
     * @var ConditionFactoryInterface
     */
    private $conditionFactory;

    public function __construct(
        DataProviderLocatorInterface $dataProviders,
        ConditionFactoryInterface $conditionFactory
    )
    {
        $this->dataProviders    = $dataProviders;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * @inheritdoc
     */
    public function match(VisitorInfo $visitorInfo, array $conditions): bool
    {
        $ruleBuilder = new RuleBuilder();

        foreach ($conditions as $conditionConfig) {
            $closure = new Closure(function() use ($visitorInfo, $conditionConfig) {
                return $this->matchCondition($visitorInfo, $conditionConfig);
            });

            $ruleBuilder->add(
                $closure,
                Boolean::fromString($conditionConfig['operator']),
                $conditionConfig['bracketLeft'],
                $conditionConfig['bracketRight']
            );
        }

        $rule = $ruleBuilder->getResult();

        return $rule->evaluate();
    }

    private function matchCondition(VisitorInfo $visitorInfo, array $config): bool
    {
        $condition = $this->conditionFactory->build($config);

        // check prerequisites - e.g. a condition without a value
        // (= all values match) does not need to fetch provider data
        // as location or browser
        // TODO does unconfigured resolve to true or false?
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
}
