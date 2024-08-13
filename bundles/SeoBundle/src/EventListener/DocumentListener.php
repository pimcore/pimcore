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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Pimcore;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
            DocumentEvents::PAGE_POST_SAVE_ACTION => 'onPagePostSaveAction',
            DocumentEvents::POST_MOVE_ACTION => 'onPostMoveAction',
        ];
    }

    public function onDocumentDelete(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

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

    public function onPagePostSaveAction(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $page = $event->getDocument();
        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        if ($page instanceof Page && $pimcore_seo_redirects['auto_create_redirects']) {
            $oldPage = $event->getArgument('oldPage');
            $task = $event->getArgument('task');
            if ($task === 'publish' || $task === 'unpublish') {
                if ($page->getPrettyUrl() !== $oldPage->getPrettyUrl()
                    && empty($oldPage->getPrettyUrl()) === false
                    && empty($page->getPrettyUrl()) === false
                ) {
                    $redirect = new Redirect();

                    $redirect->setSource($oldPage->getPrettyUrl());
                    $redirect->setTarget($page->getPrettyUrl());
                    $redirect->setStatusCode(301);
                    $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                    $redirect->save();
                }
            }
        }
    }

    public function onPostMoveAction(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        $oldDocument = $event->getArgument('oldDocument');
        $oldPath = $event->getArgument('oldPath');
        $this->createRedirectForFormerPath($document, $oldPath, $oldDocument);
    }

    private function createRedirectForFormerPath(Document $document, string $oldPath, Document $oldDocument): void
    {
        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        if ($document instanceof Document\Page || $document instanceof Document\Hardlink) {
            if (Pimcore\Tool\Admin::getCurrentUser()->isAllowed('redirects') && $pimcore_seo_redirects['auto_create_redirects']) {
                $sourceSite = Frontend::getSiteForDocument($oldDocument);
                if ($sourceSite) {
                    $oldPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $oldPath);
                }

                $targetSite = Frontend::getSiteForDocument($document);

                $this->doCreateRedirectForFormerPath($oldPath, $document->getId(), $sourceSite, $targetSite);

                if ($document->hasChildren()) {
                    $list = new Document\Listing();
                    $list->setCondition('`path` LIKE :path', [
                        'path' => $list->escapeLike($document->getRealFullPath()) . '/%',
                    ]);

                    $childrenList = $list->loadIdPathList();

                    $count = 0;

                    foreach ($childrenList as $child) {
                        $source = preg_replace('@^' . preg_quote($document->getRealFullPath(), '@') . '@', $oldDocument->getRealFullPath(), $child['path']);
                        if ($sourceSite) {
                            $source = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $source);
                        }

                        $target = $child['id'];

                        $this->doCreateRedirectForFormerPath($source, $target, $sourceSite, $targetSite);

                        $count++;
                        if ($count % 10 === 0) {
                            Pimcore::collectGarbage();
                        }
                    }
                }
            }
        }
    }

    private function doCreateRedirectForFormerPath(string $source, int $targetId, ?Site $sourceSite, ?Site $targetSite): void
    {
        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_AUTO_CREATE);
        $redirect->setRegex(false);
        $redirect->setTarget((string) $targetId);
        $redirect->setSource($source);
        $redirect->setStatusCode(301);
        $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

        if ($sourceSite) {
            $redirect->setSourceSite($sourceSite->getId());
        }

        if ($targetSite) {
            $redirect->setTargetSite($targetSite->getId());
        }

        $redirect->save();
    }
}
