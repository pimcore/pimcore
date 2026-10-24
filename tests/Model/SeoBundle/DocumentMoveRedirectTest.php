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

namespace Pimcore\Tests\Model\SeoBundle;

use Pimcore;
use Pimcore\Bundle\SeoBundle\EventListener\DocumentListener;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Moving or renaming a document through Document::save() (Studio, PHP API) must create the
 * documented auto redirects, not only the classic admin's POST_MOVE_ACTION.
 *
 * @group model.seo.redirect
 */
class DocumentMoveRedirectTest extends ModelTestCase
{
    private DocumentListener $listener;

    /**
     * @var array<int, array{string, callable}>
     */
    private array $removedListeners = [];

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        // the project config decides the container listener's flag, so swap it for ones with a fixed flag
        $dispatcher = Pimcore::getEventDispatcher();
        foreach (array_keys(DocumentListener::getSubscribedEvents()) as $eventName) {
            foreach ($dispatcher->getListeners($eventName) as $listener) {
                if (is_array($listener) && $listener[0] instanceof DocumentListener) {
                    $dispatcher->removeListener($eventName, $listener);
                    $this->removedListeners[] = [$eventName, $listener];
                }
            }
        }
        $this->useListener(new DocumentListener(['auto_create_redirects' => true]));
    }

    private function useListener(DocumentListener $listener): void
    {
        if (isset($this->listener)) {
            Pimcore::getEventDispatcher()->removeSubscriber($this->listener);
        }
        $this->listener = $listener;
        Pimcore::getEventDispatcher()->addSubscriber($listener);
    }

    protected function tearDown(): void
    {
        Pimcore::getEventDispatcher()->removeSubscriber($this->listener);
        foreach ($this->removedListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        }
        $list = new Redirect\Listing();
        $list->setCondition('type = ?', [Redirect::TYPE_AUTO_CREATE]);
        foreach ($list->load() as $redirect) {
            $redirect->delete();
        }
        TestHelper::cleanUp();
        parent::tearDown();
    }

    /**
     * @return Redirect[]
     */
    private function autoRedirects(): array
    {
        $list = new Redirect\Listing();
        $list->setCondition('type = ?', [Redirect::TYPE_AUTO_CREATE]);

        return $list->load();
    }

    public function testMoveCreatesRedirectsForDocumentAndChildren(): void
    {
        $newParent = TestHelper::createDocumentFolder('pme_t2294_new');
        $page = TestHelper::createEmptyDocumentPage('pme_t2294_page');
        $child = TestHelper::createEmptyDocumentPage('pme_t2294_child', false);
        $child->setParentId($page->getId());
        $child->save();

        $oldPagePath = $page->getRealFullPath();
        $oldChildPath = $child->getRealFullPath();

        $page->setParentId($newParent->getId());
        $page->save();

        $redirects = [];
        foreach ($this->autoRedirects() as $redirect) {
            $redirects[$redirect->getSource()] = (int) $redirect->getTarget();
        }

        $this->assertSame([
            $oldPagePath => $page->getId(),
            $oldChildPath => $child->getId(),
        ], $redirects);
    }

    public function testRenameCreatesRedirect(): void
    {
        $page = TestHelper::createEmptyDocumentPage('pme_t2294_rename');
        $oldPath = $page->getRealFullPath();

        $page->setKey('pme_t2294_renamed');
        $page->save();

        $redirects = $this->autoRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSame($oldPath, $redirects[0]->getSource());
        $this->assertSame((string) $page->getId(), $redirects[0]->getTarget());
    }

    public function testSaveWithoutPathChangeCreatesNoRedirect(): void
    {
        $page = TestHelper::createEmptyDocumentPage('pme_t2294_same');
        $page->setProperty('pme_t2294', 'text', 'x');
        $page->save();

        $this->assertSame([], $this->autoRedirects());
    }

    public function testPostMoveActionAfterSaveDoesNotDuplicate(): void
    {
        $newParent = TestHelper::createDocumentFolder('pme_t2294_dup');
        $page = TestHelper::createEmptyDocumentPage('pme_t2294_dup_page');
        $oldPath = $page->getRealFullPath();
        $oldDocument = clone $page;

        $page->setParentId($newParent->getId());
        $page->save();

        // the classic admin dispatches this right after the save
        Pimcore::getEventDispatcher()->dispatch(
            new DocumentEvent($page, ['oldDocument' => $oldDocument, 'oldPath' => $oldPath]),
            DocumentEvents::POST_MOVE_ACTION
        );

        $this->assertCount(1, $this->autoRedirects());
    }

    public function testNoRedirectWhenFlagIsOff(): void
    {
        $this->useListener(new DocumentListener(['auto_create_redirects' => false]));

        $page = TestHelper::createEmptyDocumentPage('pme_t2294_off');
        $page->setKey('pme_t2294_off_renamed');
        $page->save();

        $this->assertSame([], $this->autoRedirects());
    }
}
