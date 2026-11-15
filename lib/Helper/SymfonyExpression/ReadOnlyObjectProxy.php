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

use Closure;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Stands in for an element or a data container (localized fields, object bricks, field
 * collections, classification store) inside an expression.
 *
 * Every method call goes through __call() to a resolver that knows which getters the wrapped
 * object exposes, so the expression never holds the object itself. Keep this class free of
 * further public methods: the expression language can call every public method, static ones
 * included.
 *
 * @internal
 */
final class ReadOnlyObjectProxy
{
    /**
     * @param Closure(string, array<mixed>): mixed $methodResolver
     * @param Closure(): string $stringResolver
     */
    public function __construct(
        private readonly Closure $methodResolver,
        private readonly Closure $stringResolver
    ) {
    }

    /**
     * @param array<mixed> $arguments
     *
     * @throws SyntaxError
     */
    public function __call(string $method, array $arguments): mixed
    {
        return ($this->methodResolver)($method, $arguments);
    }

    /**
     * @throws SyntaxError
     */
    public function __get(string $name): never
    {
        throw new SyntaxError('Property access is not allowed in expressions');
    }

    public function __isset(string $name): bool
    {
        return false;
    }

    public function __toString(): string
    {
        return ($this->stringResolver)();
    }
}
