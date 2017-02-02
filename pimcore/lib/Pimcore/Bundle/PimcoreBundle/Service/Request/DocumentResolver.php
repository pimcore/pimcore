<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

class DocumentResolver extends AbstractRequestResolver
{
    /**
     * @return Document\PageSnippet|Document|null
     */
    public function getDocument(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->get('contentDocument', null);
    }

    /**
     * @param Request $request
     * @param Document $document
     */
    public function setDocument(Request $request, Document $document)
    {
        $request->attributes->set('contentDocument', $document);
    }
}
