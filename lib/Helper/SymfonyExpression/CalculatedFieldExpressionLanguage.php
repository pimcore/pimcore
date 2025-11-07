<?php
declare(strict_types=1);

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
