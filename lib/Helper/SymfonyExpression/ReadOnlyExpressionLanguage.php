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

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Expression language for expressions that are stored as configuration or data, i.e. not written
 * by a developer: the expression gets read-only stand-ins of its variables, so it can read
 * elements, field values and the data of other values, but cannot call any other method of them
 * (GHSA-93jv-p24f-gcpp). The `constant` function is not available.
 *
 * Use the service `Pimcore\Helper\SymfonyExpression\ReadOnlyExpressionLanguage`, which knows the
 * registered ReadOnlyValueAdapterInterface services, unless you need functions of your own.
 */
class ReadOnlyExpressionLanguage extends ExpressionLanguage
{
    use ReadOnlyEvaluationTrait;

    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers
     * @param iterable<ReadOnlyValueAdapterInterface> $valueAdapters
     */
    public function __construct(iterable $providers = [], iterable $valueAdapters = [])
    {
        $this->initializeReadOnlyEvaluation($valueAdapters);

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
}
