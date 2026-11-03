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
use Pimcore\Bundle\SeoBundle\Event\Model\RedirectEvent;
use Pimcore\Bundle\SeoBundle\Event\RedirectEvents;
use Pimcore\Bundle\SeoBundle\EventListener\DocumentListener;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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

    private ?Site $site = null;

    private ?User $user = null;

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
        $this->tokenStorage()->setToken(null);
        $this->user?->delete();
        if ($this->site) {
            $this->site->delete();
            RuntimeCache::getInstance()->offsetUnset('sites_path_mapping');
        }
        // saving a page inside a site renders it, which makes that site the current one for the rest of the process
        (new ReflectionProperty(Site::class, 'currentSite'))->setValue(null, null);
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
        $list->setOrderKey('source');
        $list->setOrder('ASC');

        return $list->load();
    }

    /**
     * @return array<string, int> target document id by source
     */
    private function targetsBySource(): array
    {
        $targets = [];
        foreach ($this->autoRedirects() as $redirect) {
            $targets[$redirect->getSource()] = (int) $redirect->getTarget();
        }

        return $targets;
    }

    /**
     * @param string[] $permissions
     */
    private function loginWithPermissions(array $permissions): void
    {
        $user = new User();
        $user->setParentId(0);
        $user->setName('seo_move_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions($permissions);
        $user->save();
        $this->user = $user;

        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container, so reach the shared instance
        // through the public resolver that Admin::getCurrentUser() reads from
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }

    public function testMoveCreatesRedirectsForDocumentAndChildren(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_new');
        $page = TestHelper::createEmptyDocumentPage('seo_move_page');
        $child = TestHelper::createEmptyDocumentPage('seo_move_child', false);
        $child->setParentId($page->getId());
        $child->save();

        $oldPagePath = $page->getRealFullPath();
        $oldChildPath = $child->getRealFullPath();

        $page->setParentId($newParent->getId());
        $page->save();

        $expected = [
            $oldPagePath => $page->getId(),
            $oldChildPath => $child->getId(),
        ];
        ksort($expected);

        $this->assertSame($expected, $this->targetsBySource());
    }

    public function testRenameCreatesRedirect(): void
    {
        $page = TestHelper::createEmptyDocumentPage('seo_move_rename');
        $oldPath = $page->getRealFullPath();

        $page->setKey('seo_move_renamed');
        $page->save();

        $redirects = $this->autoRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSame($oldPath, $redirects[0]->getSource());
        $this->assertSame((string) $page->getId(), $redirects[0]->getTarget());
    }

    public function testSaveWithoutPathChangeCreatesNoRedirect(): void
    {
        $page = TestHelper::createEmptyDocumentPage('seo_move_same');
        $page->setProperty('seo_move', 'text', 'x');
        $page->save();

        $this->assertSame([], $this->autoRedirects());
    }

    public function testPostMoveActionAfterSaveDoesNotDuplicate(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_dup');
        $page = TestHelper::createEmptyDocumentPage('seo_move_dup_page');
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

        $page = TestHelper::createEmptyDocumentPage('seo_move_off');
        $page->setKey('seo_move_off_renamed');
        $page->save();

        $this->assertSame([], $this->autoRedirects());
    }

    public function testPostMoveActionAloneStillCreatesRedirects(): void
    {
        $page = TestHelper::createEmptyDocumentPage('seo_move_alone');
        $oldPath = $page->getRealFullPath();
        $oldDocument = clone $page;

        Pimcore::getEventDispatcher()->dispatch(
            new DocumentEvent($page, ['oldDocument' => $oldDocument, 'oldPath' => $oldPath]),
            DocumentEvents::POST_MOVE_ACTION
        );

        $this->assertSame([$oldPath => $page->getId()], $this->targetsBySource());
    }

    public function testNestedMoveDuringPostUpdateDoesNotHideTheOuterPostMoveAction(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_nested');
        $pageA = TestHelper::createEmptyDocumentPage('seo_move_nested_a');
        $pageB = TestHelper::createEmptyDocumentPage('seo_move_nested_b');
        $oldPathA = $pageA->getRealFullPath();
        $oldDocumentA = clone $pageA;

        $movedB = false;
        $moveB = function (DocumentEvent $event) use ($pageB, $newParent, &$movedB): void {
            if (!$movedB && $event->hasArgument('oldPath')) {
                $movedB = true;
                $pageB->setParentId($newParent->getId());
                $pageB->save();
            }
        };
        Pimcore::getEventDispatcher()->addListener(DocumentEvents::POST_UPDATE, $moveB, -10);

        try {
            $pageA->setParentId($newParent->getId());
            $pageA->save();
        } finally {
            Pimcore::getEventDispatcher()->removeListener(DocumentEvents::POST_UPDATE, $moveB);
        }

        Pimcore::getEventDispatcher()->dispatch(
            new DocumentEvent($pageA, ['oldDocument' => $oldDocumentA, 'oldPath' => $oldPathA]),
            DocumentEvents::POST_MOVE_ACTION
        );

        // one redirect per moved page, none doubled by the POST_MOVE_ACTION of A
        $this->assertCount(2, $this->autoRedirects());
    }

    public function testMovingAFolderCreatesRedirectsForThePagesBelow(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_target');
        $folder = TestHelper::createDocumentFolder('seo_move_folder');
        $page = TestHelper::createEmptyDocumentPage('seo_move_in_folder', false);
        $page->setParentId($folder->getId());
        $page->save();
        $unpublished = TestHelper::createEmptyDocumentPage('seo_move_draft', false, false);
        $unpublished->setParentId($folder->getId());
        $unpublished->save();

        $email = TestHelper::createEmptyDocument('seo_move_mail', false, true, '\\Pimcore\\Model\\Document\\Email');
        $email->setParentId($folder->getId());
        $email->save();
        $subFolder = TestHelper::createDocumentFolder('seo_move_sub', false);
        $subFolder->setParentId($folder->getId());
        $subFolder->save();

        $oldPagePath = $page->getRealFullPath();
        $oldDraftPath = $unpublished->getRealFullPath();

        $folder->setParentId($newParent->getId());
        $folder->save();

        $expected = [
            $oldPagePath => $page->getId(),
            $oldDraftPath => $unpublished->getId(),
        ];
        ksort($expected);

        // neither the folder, its sub folder nor the email get a redirect, they are no redirect target
        $this->assertSame($expected, $this->targetsBySource());
    }

    public function testRedirectSourceIsRelativeToTheSiteOfTheFormerPath(): void
    {
        $siteRoot = TestHelper::createEmptyDocumentPage('seo_move_site');
        $page = TestHelper::createEmptyDocumentPage('seo_move_in_site', false);
        $page->setParentId($siteRoot->getId());
        $page->save();

        $this->site = new Site();
        $this->site->setRootId($siteRoot->getId());
        $this->site->setMainDomain('seo-move.example.com');
        $this->site->save();
        // the site path mapping of the frontend tool is cached for the request
        RuntimeCache::getInstance()->offsetUnset('sites_path_mapping');

        $newParent = TestHelper::createDocumentFolder('seo_move_out_of_site');
        $page->setParentId($newParent->getId());
        $page->save();

        $redirects = $this->autoRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSame('/' . $page->getKey(), $redirects[0]->getSource());
        $this->assertSame($this->site->getId(), $redirects[0]->getSourceSite());
    }

    public function testMovingAFolderWithASiteRootBelowUsesTheSitePerDescendant(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_site_target');
        $folder = TestHelper::createDocumentFolder('seo_move_site_folder');
        $siteRoot = TestHelper::createEmptyDocumentPage('seo_move_sub_site', false);
        $siteRoot->setParentId($folder->getId());
        $siteRoot->save();
        $inSite = TestHelper::createEmptyDocumentPage('seo_move_site_page', false);
        $inSite->setParentId($siteRoot->getId());
        $inSite->save();
        $outside = TestHelper::createEmptyDocumentPage('seo_move_outside_page', false);
        $outside->setParentId($folder->getId());
        $outside->save();

        $this->site = new Site();
        $this->site->setRootId($siteRoot->getId());
        $this->site->setMainDomain('seo-move-sub.example.com');
        $this->site->save();
        RuntimeCache::getInstance()->offsetUnset('sites_path_mapping');

        $oldOutsidePath = $outside->getRealFullPath();
        $folder->setParentId($newParent->getId());
        $folder->save();

        $bySource = [];
        foreach ($this->autoRedirects() as $redirect) {
            $bySource[$redirect->getSource()] = $redirect;
        }

        // the page inside the site keeps its public URL: no redirect for it, the site root itself redirects
        $this->assertArrayNotHasKey('/' . $inSite->getKey(), $bySource);
        $this->assertArrayNotHasKey('/' . $siteRoot->getKey() . '/' . $inSite->getKey(), $bySource);
        $this->assertSame($outside->getId(), (int) $bySource[$oldOutsidePath]->getTarget());
        $this->assertNull($bySource[$oldOutsidePath]->getSourceSite());
    }

    public function testRedirectSaveIsAtomic(): void
    {
        $page = TestHelper::createEmptyDocumentPage('seo_move_atomic', false);

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_AUTO_CREATE);
        $redirect->setSource('/' . str_repeat('x', 5000));
        $redirect->setTarget((string) $page->getId());
        $redirect->setStatusCode(301);

        $before = count((new Redirect\Listing())->load());

        // the oversized source only fails the update in strict mode
        $db = Db::get();
        $sqlMode = $db->fetchOne('SELECT @@SESSION.sql_mode');
        $db->executeStatement("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
        $failed = false;

        try {
            $redirect->save();
        } catch (Throwable) {
            $failed = true;
        } finally {
            $db->executeStatement('SET SESSION sql_mode = ?', [$sqlMode]);
        }

        $this->assertTrue($failed, 'The oversized source must fail the redirect update');
        $this->assertCount($before, (new Redirect\Listing())->load());
    }

    public function testUserWithoutRedirectsPermissionGetsNoRedirect(): void
    {
        $this->loginWithPermissions(['documents']);

        $page = TestHelper::createEmptyDocumentPage('seo_move_nopermission');
        $page->setKey('seo_move_nopermission_renamed');
        $page->save();

        $this->assertSame([], $this->autoRedirects());
    }

    public function testUserWithRedirectsPermissionGetsRedirect(): void
    {
        $this->loginWithPermissions(['documents', 'redirects']);

        $page = TestHelper::createEmptyDocumentPage('seo_move_permitted');
        $oldPath = $page->getRealFullPath();
        $page->setKey('seo_move_permitted_renamed');
        $page->save();

        $this->assertSame([$oldPath => $page->getId()], $this->targetsBySource());
    }

    public function testFailingRedirectDoesNotFailTheSaveNorSkipTheOthers(): void
    {
        $newParent = TestHelper::createDocumentFolder('seo_move_fail_target');
        $folder = TestHelper::createDocumentFolder('seo_move_fail_folder');
        $broken = TestHelper::createEmptyDocumentPage('seo_move_broken', false);
        $broken->setParentId($folder->getId());
        $broken->save();
        $fine = TestHelper::createEmptyDocumentPage('seo_move_fine', false);
        $fine->setParentId($folder->getId());
        $fine->save();
        $oldFinePath = $fine->getRealFullPath();

        $failOnBroken = static function (RedirectEvent $event): void {
            if (str_contains($event->getRedirect()->getSource(), 'seo_move_broken')) {
                throw new RuntimeException('redirect insert failed');
            }
        };
        Pimcore::getEventDispatcher()->addListener(RedirectEvents::PRE_SAVE, $failOnBroken);

        try {
            $folder->setParentId($newParent->getId());
            $folder->save();
        } finally {
            Pimcore::getEventDispatcher()->removeListener(RedirectEvents::PRE_SAVE, $failOnBroken);
        }

        $this->assertSame($newParent->getId(), Document::getById($folder->getId())->getParentId());
        $this->assertSame([$oldFinePath => $fine->getId()], $this->targetsBySource());
    }
}
