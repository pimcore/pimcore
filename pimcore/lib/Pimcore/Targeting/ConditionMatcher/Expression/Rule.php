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

namespace Pimcore\Targeting\ConditionMatcher\Expression;

use Pimcore\Targeting\ConditionMatcher\Operator\OperatorInterface;
use Pimcore\Targeting\ConditionMatcher\TokenInterface;

class Rule implements ExpressionInterface, \Countable
{
    /**
     * @var TokenInterface[]
     */
    private $tokens = [];

    /**
     * @param TokenInterface[] $tokens
     */
    public function __construct(array $tokens = [])
    {
        foreach ($tokens as $token) {
            $this->addToken($token);
        }
    }

    public function addToken(TokenInterface $token)
    {
        $this->tokens[] = $token;
    }

    public function hasTokens(): bool
    {
        return $this->count() > 0;
    }

    public function count(): int
    {
        return count($this->tokens);
    }

    public function evaluate(): bool
    {
        if (empty($this->tokens)) {
            throw new \RuntimeException('Need at least one token to evaluate a rule');
        }

        if (count($this->tokens) === 1) {
            $token = $this->getToken(0);

            return $token->evaluate();
        }

        $lastIndex  = count($this->tokens) - 1;
        $lastResult = null;

        for ($i = 0; $i <= $lastIndex;) {
            // if we don't have a last result, get the current token
            // otherwise use the last result as it is the result of
            // the current token with the previous one
            if (null === $lastResult) {
                $lastResult = $this->getToken($i)->evaluate();
            }

            $resultA = $lastResult;
            $resultB = $this->getToken($i + 2)->evaluate();

            $operator   = $this->getOperator($i + 1);
            $lastResult = $operator->operate($resultA, $resultB);

            $i += 2;
            if ($i === $lastIndex) {
                break;
            }
        }

        return $lastResult;
    }

    private function getToken(int $index): ExpressionInterface
    {
        if (!isset($this->tokens[$index])) {
            throw new \OutOfBoundsException(sprintf('Expected token at index %d, but index does not exist', $index));
        }

        $token = $this->tokens[$index];
        if (!$token instanceof ExpressionInterface) {
            throw new \RuntimeException(sprintf(
                'Expected token at index %d, got "%s" instead',
                $index,
                get_class($token)
            ));
        }

        return $token;
    }

    private function getOperator(int $index): OperatorInterface
    {
        if (!isset($this->tokens[$index])) {
            throw new \OutOfBoundsException(sprintf('Expected operator at index %d, but index does not exist', $index));
        }

        $token = $this->tokens[$index];
        if (!$token instanceof OperatorInterface) {
            throw new \RuntimeException(sprintf(
                'Expected operator at index %d, got "%s" instead',
                $index,
                get_class($token)
            ));
        }

        return $token;
    }
}
