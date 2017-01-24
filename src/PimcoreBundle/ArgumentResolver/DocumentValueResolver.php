<?php

namespace PimcoreBundle\ArgumentResolver;

use Pimcore\Model\Document as DocumentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Adds support for type hinting controller actions against `Document $document` and getting the current document.
 */
class DocumentValueResolver implements ArgumentValueResolverInterface
{
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

        $document = $request->get('contentDocument');

        return $document && $document instanceof DocumentModel;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return DocumentModel
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $request->get('contentDocument');
    }
}
