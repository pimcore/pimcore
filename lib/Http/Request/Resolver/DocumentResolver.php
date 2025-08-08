<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

class DocumentResolver extends AbstractRequestResolver
{
    public function getDocument(Request $request = null): ?Document
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
