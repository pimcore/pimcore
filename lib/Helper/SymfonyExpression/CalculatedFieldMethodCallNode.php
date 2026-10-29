<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Helper\SymfonyExpression;

use LogicException;
use RuntimeException;
use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Method call node that checks the receiver against CalculatedFieldMethodCallPolicy before the
 * call is made - the receiver type is only known at evaluation time.
 *
 * @internal
 */
final class CalculatedFieldMethodCallNode extends GetAttrNode
{
    public static function fromGetAttrNode(GetAttrNode $node): self
    {
        $methodCallNode = new self($node->nodes['node'], $node->nodes['attribute'], $node->nodes['arguments'], self::METHOD_CALL);
        $methodCallNode->attributes = $node->attributes;

        return $methodCallNode;
    }

    public function compile(Compiler $compiler): void
    {
        throw new LogicException('Calculated value expressions cannot be compiled.');
    }

    public function evaluate(array $functions, array $values): mixed
    {
        $this->attributes['is_short_circuited'] = false;

        $receiver = $this->nodes['node']->evaluate($functions, $values);

        if (null === $receiver && $this->nodes['attribute']->isNullSafe) {
            $this->attributes['is_short_circuited'] = true;

            return null;
        }
        if (null === $receiver && self::isChainShortCircuited($this->nodes['node'])) {
            return null;
        }

        $method = $this->nodes['attribute']->attributes['value'];

        if (!is_object($receiver)) {
            throw new RuntimeException(sprintf('Unable to call method "%s" of non-object "%s".', $method, $this->nodes['node']->dump()));
        }

        CalculatedFieldMethodCallPolicy::assertMethodCallAllowed($receiver, $method);

        return $receiver->$method(...array_values($this->nodes['arguments']->evaluate($functions, $values)));
    }

    private static function isChainShortCircuited(Node $node): bool
    {
        return $node instanceof GetAttrNode
            && ($node->attributes['is_short_circuited'] || self::isChainShortCircuited($node->nodes['node']));
    }
}
