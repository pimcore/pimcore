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
     * Method names that are never allowed, even though they match the getter naming
     * convention below: they expose infrastructure (e.g. the DBAL connection) rather
     * than object data.
     */
    private const FORBIDDEN_METHODS = ['getdao', 'getresource'];

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
     * Only plain read-only accessors (get-, is- or has-prefixed) may be called on the object
     * graph exposed to calculated-value expressions, and never the ones that hand out
     * infrastructure objects (e.g. getDao()). This keeps the documented `object.getXxx()` /
     * `data.getXxx()` usage working while preventing arbitrary method invocation
     * (saving/deleting objects) and reaching the database connection through the Dao layer.
     */
    public function parse(Expression|string $expression, array $names, int $flags = 0): ParsedExpression
    {
        $parsedExpression = parent::parse($expression, $names, $flags);
        $this->assertNoDisallowedMethodCalls($parsedExpression->getNodes());

        return $parsedExpression;
    }

    private function assertNoDisallowedMethodCalls(Node $node): void
    {
        if ($node instanceof GetAttrNode && GetAttrNode::METHOD_CALL === $node->attributes['type']) {
            $attributeNode = $node->nodes['attribute'];
            $method = $attributeNode instanceof ConstantNode ? $attributeNode->attributes['value'] : null;

            if (!is_string($method) || !$this->isAllowedMethodName($method)) {
                throw new SyntaxError(sprintf('Method call `%s` is not allowed in calculated value expressions', is_string($method) ? $method : '?'));
            }
        }

        foreach ($node->nodes as $childNode) {
            if ($childNode instanceof Node) {
                $this->assertNoDisallowedMethodCalls($childNode);
            }
        }
    }

    private function isAllowedMethodName(string $method): bool
    {
        if (in_array(strtolower($method), self::FORBIDDEN_METHODS, true)) {
            return false;
        }

        return 1 === preg_match('/^(get|is|has)[A-Z0-9]/', $method);
    }
}
