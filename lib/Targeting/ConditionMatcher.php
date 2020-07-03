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

use Pimcore\Targeting\Condition\ConditionInterface;
use Pimcore\Targeting\Condition\EventDispatchingConditionInterface;
use Pimcore\Targeting\Condition\VariableConditionInterface;
use Pimcore\Targeting\ConditionMatcher\ExpressionBuilder;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $collectedVariables = [];

    public function __construct(
        ConditionFactoryInterface $conditionFactory,
        DataLoaderInterface $dataLoader,
        EventDispatcherInterface $eventDispatcher,
        ExpressionLanguage $expressionLanguage,
        LoggerInterface $logger
    ) {
        $this->conditionFactory = $conditionFactory;
        $this->dataLoader = $dataLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->expressionLanguage = $expressionLanguage;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function match(VisitorInfo $visitorInfo, array $conditions, bool $collectVariables = false): bool
    {
        // reset internal state
        $this->collectedVariables = [];

        $count = count($conditions);
        if (0 === $count) {
            // no conditions -> rule matches
            return true;
        } elseif (1 === $count) {
            // no need to build up expression if there's only one condition
            return $this->matchCondition($visitorInfo, $conditions[0], $collectVariables);
        }

        $expressionBuilder = new ExpressionBuilder();

        foreach ($conditions as $conditionConfig) {
            $conditionResult = $this->matchCondition($visitorInfo, $conditionConfig, $collectVariables);

            $expressionBuilder->addCondition($conditionConfig, $conditionResult);
        }

        $expression = $expressionBuilder->getExpression();
        $values = $expressionBuilder->getValues();
        $result = $this->expressionLanguage->evaluate($expression, $values);

        return (bool)$result;
    }

    /**
     * @inheritDoc
     */
    public function getCollectedVariables(): array
    {
        return $this->collectedVariables;
    }

    private function matchCondition(VisitorInfo $visitorInfo, array $config, bool $collectVariables = false): bool
    {
        try {
            $condition = $this->conditionFactory->build($config);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return false;
        }

        // check prerequisites - e.g. a condition without a value
        // (= all values match) does not need to fetch provider data
        // as location or browser
        if (!$condition->canMatch()) {
            return false;
        }

        if ($condition instanceof DataProviderDependentInterface) {
            $this->dataLoader->loadDataFromProviders($visitorInfo, $condition->getDataProviderKeys());
        }

        if ($condition instanceof EventDispatchingConditionInterface) {
            $condition->preMatch($visitorInfo, $this->eventDispatcher);
        }

        $result = false;

        try {
            $result = $condition->match($visitorInfo);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return false;
        }

        if ($collectVariables) {
            $this->collectConditionVariables($config, $condition);
        }

        if ($condition instanceof EventDispatchingConditionInterface) {
            $condition->postMatch($visitorInfo, $this->eventDispatcher);
        }

        return $result;
    }

    private function collectConditionVariables(array $config, ConditionInterface $condition)
    {
        $data = [
            'type' => $config['type'],
        ];

        if ($condition instanceof VariableConditionInterface) {
            $variables = $condition->getMatchedVariables();

            if (!empty($variables)) {
                $data['data'] = $variables;
            }
        }

        $this->collectedVariables[] = $data;
    }
}
