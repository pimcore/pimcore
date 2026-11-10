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
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Makes an ExpressionLanguage evaluate expressions against read-only stand-ins of the variables
 * (GHSA-93jv-p24f-gcpp): an expression can read data, but cannot call arbitrary methods of the
 * objects it gets. Functions are application code and get the original values; the result of an
 * expression is the original value as well. Expressions cannot be compiled.
 *
 * Call initializeReadOnlyEvaluation() before the parent constructor, which already registers
 * functions.
 *
 * @internal
 */
trait ReadOnlyEvaluationTrait
{
    private ?ReadOnlyValueWrapper $readOnlyValueWrapper = null;

    public function evaluate(Expression|string $expression, array $values = []): mixed
    {
        $valueWrapper = $this->getReadOnlyValueWrapper();

        return $valueWrapper->unwrap(parent::evaluate($expression, $valueWrapper->wrapVariables($values)));
    }

    /**
     * Compiled expressions call methods on whatever objects they get later on, so they would bypass
     * the read-only stand-ins.
     *
     * @throws LogicException
     */
    public function compile(Expression|string $expression, array $names = []): string
    {
        throw new LogicException('Compiling expressions is not supported, they are evaluated with read-only values');
    }

    public function register(string $name, callable $compiler, callable $evaluator): void
    {
        $valueWrapper = $this->getReadOnlyValueWrapper();

        parent::register(
            $name,
            $compiler,
            static fn (array $variables, mixed ...$arguments): mixed => $valueWrapper->wrapFunctionResult(
                $evaluator($valueWrapper->unwrap($variables), ...$valueWrapper->unwrap($arguments))
            )
        );
    }

    /**
     * @param iterable<ReadOnlyValueAdapterInterface> $valueAdapters
     */
    private function initializeReadOnlyEvaluation(iterable $valueAdapters): void
    {
        $this->readOnlyValueWrapper = new ReadOnlyValueWrapper($valueAdapters);
    }

    private function getReadOnlyValueWrapper(): ReadOnlyValueWrapper
    {
        return $this->readOnlyValueWrapper ??= new ReadOnlyValueWrapper();
    }
}
