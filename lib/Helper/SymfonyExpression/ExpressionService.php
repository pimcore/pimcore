<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
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
