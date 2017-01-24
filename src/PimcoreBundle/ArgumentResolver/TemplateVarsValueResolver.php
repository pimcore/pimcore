<?php

namespace PimcoreBundle\ArgumentResolver;

use PimcoreBundle\Service\Request\DocumentResolver;
use PimcoreBundle\Service\Request\EditmodeResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class TemplateVarsValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param EditmodeResolver $editmodeResolver
     * @param DocumentResolver $documentResolver
     */
    public function __construct(EditmodeResolver $editmodeResolver, DocumentResolver $documentResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === 'array' && $argument->getName() === 'templateVars';
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator|array
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield [
            'editmode' => $this->editmodeResolver->isEditmode($request),
            'document' => $this->documentResolver->getDocument($request)
        ];
    }

}
