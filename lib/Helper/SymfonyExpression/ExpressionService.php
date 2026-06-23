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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @internal
 */
final class ExpressionService implements ExpressionServiceInterface
{
    public function evaluate(
        string $condition,
        array $contentVariables
    ): bool {
        $expressionLanguage = new ExpressionLanguage();
        //overwrite constant function to avoid exposing internal information
        $expressionLanguage->register('constant', function () {
            throw new SyntaxError('`constant` function not available');
        }, function () {
            throw new SyntaxError('`constant` function not available');
        });

        return (bool)$expressionLanguage->evaluate($condition, $contentVariables);
    }
}
