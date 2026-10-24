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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Pimcore;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class DocumentListener implements EventSubscriberInterface
{
    private const MAX_HANDLED_MOVES = 100;

    /**
     * Former path by document id of the moves/renames that POST_UPDATE already created redirects for,
     * so a POST_MOVE_ACTION dispatched right after the save (classic admin) does not create them twice.
     * The entry is dropped when POST_MOVE_ACTION uses it; the list is capped for long-running workers
     * where no POST_MOVE_ACTION follows.
     *
     * @var array<int, string>
     */
    private array $handledMoves = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $redirectsConfig = null;

    /**
     * @param array<string, mixed>|null $redirectsConfig the `pimcore_seo.redirects` config, read from the container when null
     */
    public function __construct(?array $redirectsConfig = null)
    {
        $this->redirectsConfig = $redirectsConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
            DocumentEvents::POST_UPDATE => 'onDocumentUpdate',
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

    /**
     * Document::save() adds the former full path to POST_UPDATE when the document was moved or renamed,
     * regardless of who triggered it (Studio, PHP API, console).
     * Failing to create a redirect must not fail the save, which has already moved the document.
     */
    public function onDocumentUpdate(DocumentEvent $event): void
    {
        $oldPath = $event->hasArgument('oldPath') ? $event->getArgument('oldPath') : null;
        if (!is_string($oldPath) || $oldPath === '' || !PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        $this->handledMoves[$document->getId()] = $oldPath;
        $this->handledMoves = array_slice($this->handledMoves, -self::MAX_HANDLED_MOVES, null, true);

        try {
            $oldDocument = clone $document;
            $oldDocument->setKey(basename($oldPath));
            $oldDocument->setPath(rtrim(dirname($oldPath), '/') . '/');

            $this->createRedirectForFormerPath($document, $oldPath, $oldDocument);
        } catch (Throwable $e) {
            Logger::error('Could not create the auto redirects for the moved document ' . $document->getId() . ': ' . $e->getMessage());
        }
    }

    public function onPagePostSaveAction(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $page = $event->getDocument();
        $pimcore_seo_redirects = $this->getRedirectsConfig();
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
        if (($this->handledMoves[$document->getId()] ?? null) === $oldPath) {
            unset($this->handledMoves[$document->getId()]);

            return;
        }

        $this->createRedirectForFormerPath($document, $oldPath, $oldDocument);
    }

    private function createRedirectForFormerPath(Document $document, string $oldPath, Document $oldDocument): void
    {
        // without a user (console, background worker) the opt-in flag alone decides
        $user = Pimcore\Tool\Admin::getCurrentUser();
        if (!$this->getRedirectsConfig()['auto_create_redirects'] || ($user !== null && !$user->isAllowed('redirects'))) {
            return;
        }

        $sourceSite = Frontend::getSiteForDocument($oldDocument);
        $targetSite = Frontend::getSiteForDocument($document);

        if ($document instanceof Page || $document instanceof Hardlink) {
            $this->doCreateRedirectForFormerPath($this->stripSiteRootPath($oldPath, $sourceSite), $document->getId(), $sourceSite, $targetSite);
        }

        // a moved folder or page redirects the documents below it, too (a folder is no redirect target)
        if ($document->hasChildren(true)) {
            $list = new Document\Listing();
            $list->setUnpublished(true);
            $list->setCondition('`path` LIKE :path AND `type` != \'folder\'', [
                'path' => $list->escapeLike($document->getRealFullPath()) . '/%',
            ]);

            $count = 0;

            foreach ($list->loadIdPathList() as $child) {
                $source = preg_replace('@^' . preg_quote($document->getRealFullPath(), '@') . '@', $oldDocument->getRealFullPath(), $child['path']);

                $this->doCreateRedirectForFormerPath($this->stripSiteRootPath($source, $sourceSite), $child['id'], $sourceSite, $targetSite);

                $count++;
                if ($count % 10 === 0) {
                    Pimcore::collectGarbage();
                }
            }
        }
    }

    private function stripSiteRootPath(string $path, ?Site $site): string
    {
        return $site ? preg_replace('@^' . preg_quote($site->getRootPath(), '@') . '@', '', $path) : $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRedirectsConfig(): array
    {
        return $this->redirectsConfig ?? Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
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

        try {
            $redirect->save();
        } catch (Throwable $e) {
            // e.g. a source longer than the column: skip this one, the other redirects are still created
            Logger::error('Could not save the auto redirect from "' . $source . '": ' . $e->getMessage());
        }
    }
}
