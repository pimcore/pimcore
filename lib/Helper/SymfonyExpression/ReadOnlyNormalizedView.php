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

use ArrayAccess;
use Closure;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Read-only view of a normalized value (the array a field definition's normalize() returns)
 * inside an expression.
 *
 * `getFoo()` reads the key `foo`, case-insensitively; `getRow__col()` reads `[row][col]`.
 * Getters whose result is derived rather than stored (e.g. the unit of a quantity value) are
 * passed in as $derivedGetters. Keep this class free of further public methods: the expression
 * language can call every public method, static ones included.
 *
 * @internal
 *
 * @implements ArrayAccess<array-key, mixed>
 */
final class ReadOnlyNormalizedView implements ArrayAccess
{
    /**
     * @param array<array-key, mixed> $data
     * @param Closure(mixed): mixed $itemWrapper turns a nested normalized value into something an expression may hold
     * @param array<string, Closure(array<mixed>): mixed> $derivedGetters keyed by lower-case method name
     * @param Closure(): string $stringResolver
     */
    public function __construct(
        private readonly array $data,
        private readonly Closure $itemWrapper,
        private readonly array $derivedGetters,
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
        $derivedGetter = $this->derivedGetters[strtolower($method)] ?? null;
        if ($derivedGetter !== null) {
            return $derivedGetter($arguments);
        }

        if (!str_starts_with($method, 'get') || strlen($method) === 3) {
            throw $this->createNotAvailableError($method);
        }

        $value = $this->data;
        foreach (explode('__', substr($method, 3)) as $key) {
            $matchingKey = is_array($value) ? $this->findKey($value, $key) : null;
            if ($matchingKey === null) {
                throw $this->createNotAvailableError($method);
            }

            $value = $value[$matchingKey];
        }

        return ($this->itemWrapper)($value);
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

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return ($this->itemWrapper)($this->data[$offset] ?? null);
    }

    /**
     * @throws SyntaxError
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new SyntaxError('Expressions are read-only');
    }

    /**
     * @throws SyntaxError
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new SyntaxError('Expressions are read-only');
    }

    private function createNotAvailableError(string $method): SyntaxError
    {
        return new SyntaxError(sprintf('`%s()` is not available in expressions', $method));
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function findKey(array $data, string $key): int|string|null
    {
        if (array_key_exists($key, $data)) {
            return $key;
        }

        foreach (array_keys($data) as $candidate) {
            if (strcasecmp((string) $candidate, $key) === 0) {
                return $candidate;
            }
        }

        return null;
    }
}
