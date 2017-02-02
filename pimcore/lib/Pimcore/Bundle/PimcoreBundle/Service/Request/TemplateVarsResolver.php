<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class TemplateVarsResolver
{
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param DocumentResolver $documentResolver
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(DocumentResolver $documentResolver, EditmodeResolver $editmodeResolver)
    {
        $this->documentResolver = $documentResolver;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @param Request|null $request
     * @return array
     */
    public function getTemplateVars(Request $request = null)
    {
        return [
            'document' => $this->documentResolver->getDocument($request),
            'editmode' => $this->editmodeResolver->isEditmode($request)
        ];
    }
}
