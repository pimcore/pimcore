<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DocumentResolver extends AbstractRequestResolver
{
    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
    }

    /**
     * @param Request|null $request
     *
     * @return null|Document|Document\PageSnippet
     */
    public function getDocument(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $content = $request->get(DynamicRouter::CONTENT_KEY, null);
        if ($content instanceof Document) {
            return $content;
        }

        return null;
    }

    /**
     * @param Request $request
     * @param Document $document
     */
    public function setDocument(Request $request, Document $document)
    {
        $request->attributes->set(DynamicRouter::CONTENT_KEY, $document);
        if ($document->getProperty('language')) {
            $request->setLocale($document->getProperty('language'));
        }
    }
}
