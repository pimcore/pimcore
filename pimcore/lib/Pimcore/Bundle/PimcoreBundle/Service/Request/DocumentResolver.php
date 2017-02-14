<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
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

        return $request->get(DynamicRouter::CONTENT_KEY, null);
    }

    /**
     * @param Request $request
     * @param Document $document
     */
    public function setDocument(Request $request, Document $document)
    {
        $request->attributes->set(DynamicRouter::CONTENT_KEY, $document);
    }
}
