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

/**
 * Declares which getters an expression may call on a value of a bundle-specific type, when the
 * value is passed to a ReadOnlyExpressionLanguage (or reached through another value).
 *
 * Without an adapter such a value can be passed on to expression functions, but no method can be
 * called on it. Services implementing this interface are tagged
 * `pimcore.expression.read_only_value_adapter` automatically.
 */
interface ReadOnlyValueAdapterInterface
{
    public function supports(object $value): bool;

    /**
     * The getters an expression may call on the value, keyed by lower-case method name. A getter
     * gets the (scalar) arguments of the call; whatever it returns is made read-only again before
     * the expression sees it, so it may return model objects.
     *
     * @return array<string, Closure(array<mixed>): mixed>
     */
    public function getGetters(object $value): array;
}
