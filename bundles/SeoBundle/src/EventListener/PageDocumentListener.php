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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


namespace Pimcore\Bundle\SeoBundle\EventListener;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageDocumentListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
        ];
    }

    public function onDocumentDelete(DocumentEvent $event) : void
    {
        $document = $event->getDocument();
        if ($document instanceof Page || $document instanceof Hardlink) {
            // check for redirects pointing to this document, and delete them too
            $redirects = new Redirect\Listing();
            $redirects->setCondition('target = ?', $document->getId());
            $redirects->load();

            foreach ($redirects->getRedirects() as $redirect) {
                $redirect->delete();
            }
        }
    }

}
