<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

class DocumentResolver extends AbstractRequestResolver
{
    public function getDocument(?Request $request = null): ?Document
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $content = $request->attributes->get(DynamicRouter::CONTENT_KEY);
        if ($content instanceof Document) {
            return $content;
        }

        return null;
    }

    public function setDocument(Request $request, Document $document): void
    {
        $request->attributes->set(DynamicRouter::CONTENT_KEY, $document);
        if ($document->getProperty('language')) {
            $request->setLocale($document->getProperty('language'));
        }
    }
}
