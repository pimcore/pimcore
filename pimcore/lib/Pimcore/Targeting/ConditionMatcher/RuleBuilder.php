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

namespace Pimcore\Targeting\ConditionMatcher;

use Pimcore\Targeting\ConditionMatcher\Expression\ExpressionInterface;
use Pimcore\Targeting\ConditionMatcher\Expression\Rule;
use Pimcore\Targeting\ConditionMatcher\Operator\Boolean;
use Pimcore\Targeting\ConditionMatcher\Operator\OperatorInterface;

class RuleBuilder
{
    /**
     * @var int
     */
    private $currentIndex = 0;

    /**
     * @var Rule[]
     */
    private $ruleStack = [];

    public function __construct()
    {
        $this->ruleStack = [new Rule()];
    }

    public function getResult(): Rule
    {
        if (count($this->ruleStack) > 1) {
            throw new \RuntimeException('Rule stack still contains more than one rule. Please ensure all brackets are properly closed.');
        }

        return $this->ruleStack[0];
    }

    public function getCurrentRule(): Rule
    {
        return $this->ruleStack[$this->currentIndex];
    }

    public function add(
        ExpressionInterface $expression,
        OperatorInterface $operator = null,
        bool $bracketLeft = false,
        bool $bracketRight = false
    ): self
    {
        $currentRule = $this->ruleStack[$this->currentIndex];

        // only add an operator if the current rule already has tokens
        if ($currentRule->hasTokens()) {
            if (null === $operator) {
                $operator = Boolean::fromString(Boolean::AND);
            }

            $currentRule->addToken($operator);
        }

        if ($bracketLeft) {
            $this->openBracket();
        }

        $this->getCurrentRule()->addToken($expression);

        if ($bracketRight) {
            $this->closeBracket();
        }

        return $this;
    }

    private function openBracket(): self
    {
        $this->ruleStack[] = new Rule();
        $this->currentIndex++;

        return $this;
    }

    private function closeBracket(): self
    {
        if (count($this->ruleStack) === 1) {
            throw new \UnderflowException('Can\'t close bracket as the current rule already is the root rule');
        }

        $rule = array_pop($this->ruleStack);
        $this->currentIndex--;

        $this->getCurrentRule()->addToken($rule);

        return $this;
    }
}
