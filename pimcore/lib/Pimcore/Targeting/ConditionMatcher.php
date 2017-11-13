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

use Pimcore\Targeting\ConditionMatcher\ExpressionBuilder;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionMatcher implements ConditionMatcherInterface
{
    /**
     * @var ConditionFactoryInterface
     */
    private $conditionFactory;

    /**
     * @var DataLoaderInterface
     */
    private $dataLoader;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(
        ConditionFactoryInterface $conditionFactory,
        DataLoaderInterface $dataLoader,
        ExpressionLanguage $expressionLanguage
    )
    {
        $this->conditionFactory   = $conditionFactory;
        $this->dataLoader         = $dataLoader;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @inheritdoc
     */
    public function match(VisitorInfo $visitorInfo, array $conditions): bool
    {
        $conditions = array_values($conditions);

        $count = count($conditions);
        if (0 === $count) {
            // no conditions -> rule matches
            return true;
        } elseif (1 === $count) {
            // no need to build up expression if there's only one condition
            return $this->matchCondition($visitorInfo, $conditions[0]);
        }

        $expressionBuilder = new ExpressionBuilder();

        foreach ($conditions as $conditionConfig) {
            $conditionResult = $this->matchCondition($visitorInfo, $conditionConfig);

            $expressionBuilder->addCondition($conditionConfig, $conditionResult);
        }

        $expression = $expressionBuilder->getExpression();
        $values     = $expressionBuilder->getValues();
        $result     = $this->expressionLanguage->evaluate($expression, $values);

        return (bool)$result;
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

        if ($condition instanceof DataProviderDependentInterface) {
            $this->dataLoader->loadDataFromProviders($visitorInfo, $condition->getDataProviderKeys());
        }

        return $condition->match($visitorInfo);
    }
}
