<?php

namespace PimcoreBundle\Service\Request;

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
}
