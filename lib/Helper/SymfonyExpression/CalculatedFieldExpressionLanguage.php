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
}
