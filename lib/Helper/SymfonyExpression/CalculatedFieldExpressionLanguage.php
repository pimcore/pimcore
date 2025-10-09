<?php

namespace Pimcore\Helper\SymfonyExpression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
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
        $this->register('constant', function ($str) {
            throw new SyntaxError('`constant` function not available');
        }, function ($arguments, $str) {
            throw new SyntaxError('`constant` function not available');
        });

        foreach ($providers as $provider) {
            if ($provider instanceof ExpressionFunctionProviderInterface) {
                $this->registerProvider($provider);
            }
        }
    }
}
