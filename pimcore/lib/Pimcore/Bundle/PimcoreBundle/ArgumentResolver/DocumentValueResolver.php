<?php

namespace Pimcore\Bundle\PimcoreBundle\ArgumentResolver;

use Pimcore\Model\Document as DocumentModel;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Adds support for type hinting controller actions against `Document $document` and getting the current document.
 */
class DocumentValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param DocumentResolver $documentResolver
     */
    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== DocumentModel::class) {
            return false;
        }

        if ($argument->getName() !== 'document') {
            return false;
        }

        $document = $this->documentResolver->getDocument($request);

        return $document && $document instanceof DocumentModel;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator|DocumentModel
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->documentResolver->getDocument($request);
    }
}
