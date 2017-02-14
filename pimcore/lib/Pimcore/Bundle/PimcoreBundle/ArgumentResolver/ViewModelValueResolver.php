<?php

namespace Pimcore\Bundle\PimcoreBundle\ArgumentResolver;

use Pimcore\Bundle\PimcoreBundle\Service\Request\ViewModelResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ViewModelValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ViewModelResolver
     */
    protected $viewModelResolver;

    /**
     * @param ViewModelResolver $viewModelResolver
     */
    public function __construct(ViewModelResolver $viewModelResolver)
    {
        $this->viewModelResolver = $viewModelResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === ViewModel::class && $argument->getName() === 'view';
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator|ViewModel
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->viewModelResolver->getViewModel($request);
    }
}
