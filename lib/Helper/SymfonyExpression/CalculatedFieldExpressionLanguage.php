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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class CalculatedFieldExpressionLanguage extends ExpressionLanguage
{
    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        parent::__construct();

        //overwrite constant function to avoid exposing internal information
        $this->register('constant', function () {
            throw new SyntaxError('`constant` function not available');
        }, function () {
            throw new SyntaxError('`constant` function not available');
        });

        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    /**
     * Calculated value expressions may only read data of the object graph: property access is
     * rejected, and every method call is checked against CalculatedFieldMethodCallPolicy - the
     * method name here, the receiver when the expression is evaluated. This keeps the documented
     * `object.getXxx()` / `data.getXxx()` usage working while preventing arbitrary method
     * invocation, SQL built from expression input and reaching the database connection.
     */
    public function parse(Expression|string $expression, array $names, int $flags = 0): ParsedExpression
    {
        $parsedExpression = parent::parse($expression, $names, $flags);

        return new ParsedExpression((string) $expression, $this->sandbox($parsedExpression->getNodes()));
    }

    private function sandbox(Node $node): Node
    {
        foreach ($node->nodes as $key => $childNode) {
            if ($childNode instanceof Node) {
                $node->nodes[$key] = $this->sandbox($childNode);
            }
        }

        if (!$node instanceof GetAttrNode || $node instanceof CalculatedFieldMethodCallNode) {
            return $node;
        }

        if (GetAttrNode::PROPERTY_CALL === $node->attributes['type']) {
            throw new SyntaxError('Property access is not allowed in calculated value expressions');
        }

        if (GetAttrNode::METHOD_CALL !== $node->attributes['type']) {
            return $node;
        }

        $attributeNode = $node->nodes['attribute'];
        $method = $attributeNode instanceof ConstantNode ? $attributeNode->attributes['value'] : null;

        if (!is_string($method) || !CalculatedFieldMethodCallPolicy::isAllowedMethodName($method)) {
            throw new SyntaxError(sprintf('Method call `%s` is not allowed in calculated value expressions', is_string($method) ? $method : '?'));
        }

        return CalculatedFieldMethodCallNode::fromGetAttrNode($node);
    }
}
