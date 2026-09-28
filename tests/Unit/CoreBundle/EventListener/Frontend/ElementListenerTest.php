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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Pimcore\Security\User\UserLoader;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Regression coverage for GHSA-v36c-r89g-2226: the frontend admin-preview path authenticated
 * the requester but never checked whether they were allowed to see the element. Any active
 * backend user - regardless of workspace or "versions" permission - could read any unpublished
 * document (?pimcore_admin=1) or any historical document version by id (?pimcore_version=<id>).
 */
class ElementListenerTest extends TestCase
{
    private function makeListener(
        DocumentResolver $documentResolver,
        RequestHelper $requestHelper,
        UserLoader $userLoader,
        ?EditmodeResolver $editmodeResolver = null
    ): ElementListener {
        // handleObjectParams() is unrelated to the document permission logic under test here,
        // but it unconditionally touches Element\Service::getElementFromSession() (a real
        // TmpStore/DB lookup) whenever an admin user is present. Stub it out so this remains a
        // true unit test, consistent with TestCase::needsDb() defaulting to false.
        $listener = $this->getMockBuilder(ElementListener::class)
            ->setConstructorArgs([
                $documentResolver,
                $editmodeResolver ?? $this->createMock(EditmodeResolver::class),
                $requestHelper,
                $userLoader,
            ])
            ->onlyMethods(['handleObjectParams'])
            ->getMock();
        $listener->setLogger(new NullLogger());

        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);
        $listener->setPimcoreContextResolver($contextResolver);

        return $listener;
    }

    private function dispatch(ElementListener $listener, Request $request): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ControllerEvent($kernel, static function (): void {
        }, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelController($event);
    }

    private function adminRequestHelper(): RequestHelper
    {
        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(true);

        return $requestHelper;
    }

    public function testUnpublishedDocumentIsDeniedForLoggedInUserWithoutViewPermission(): void
    {
        // The exact defect: any active backend session ("$user" truthy) was sufficient, with
        // no check that this particular user may view this particular unpublished document.
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(false);
        $document->method('isAllowed')->with('view', $this->anything())->willReturn(false);
        $document->method('getFullPath')->willReturn('/poc-secret');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader);

        $this->expectException(AccessDeniedHttpException::class);
        $this->dispatch($listener, new Request(['pimcore_admin' => '1']));
    }

    public function testUnpublishedDocumentIsServedForLoggedInUserWithViewPermission(): void
    {
        // Legitimate behaviour must keep working: a user who is actually allowed to view the
        // unpublished document (e.g. an admin, or a user with a matching workspace) is not denied.
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(false);
        $document->method('isAllowed')->with('view', $this->anything())->willReturn(true);
        $document->method('getFullPath')->willReturn('/poc-secret');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        $documentResolver->expects($this->once())->method('setDocument')->with($this->anything(), $document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader);

        $this->dispatch($listener, new Request(['pimcore_admin' => '1']));
    }

    public function testPublishedDocumentIsNotAffectedByViewPermission(): void
    {
        // The permission check only guards unpublished documents; published content must not
        // start requiring a "view" grant as a side effect of this fix.
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->expects($this->never())->method('isAllowed');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->expects($this->never())->method('getUser');

        $listener = $this->makeListener($documentResolver, $requestHelper, $userLoader);

        $this->dispatch($listener, new Request());
    }

    // -----------------------------------------------------------------------
    // denyAccessUnlessVersionsAllowed() via editmode/studio-preview - these substitute in the
    // document's latest (possibly unpublished) version and, unlike the isPublished() guard,
    // are reachable for *published* documents too (see GHSA-v36c-r89g-2226).
    // -----------------------------------------------------------------------

    public function testEditmodeOnPublishedDocumentIsDeniedWithoutVersionsPermission(): void
    {
        // Any active backend session was enough to reach getLatestVersion() here, regardless
        // of workspace - even though the document is published and the top-level guard added
        // for the unpublished case never applies to it. Plain Document (not PageSnippet) is
        // deliberate: it keeps this test off the real getLatestVersion()/Dao delegation
        // (a PHP magic-__call method PHPUnit cannot stub) while still exercising the guard,
        // which runs before the instanceof PageSnippet substitution is ever reached.
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(false);
        $document->method('getFullPath')->willReturn('/poc-published');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn(true);

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader, $editmodeResolver);

        // pimcore_studio=1 skips the session-stored-draft lookup (self-scoped, not part of this
        // defect) and goes straight to the getLatestVersion() substitution under test.
        $request = new Request(['pimcore_editmode' => '1', 'pimcore_studio' => '1']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(AccessDeniedHttpException::class);
        $this->dispatch($listener, $request);
    }

    public function testEditmodeOnPublishedDocumentServesLatestVersionWithVersionsPermission(): void
    {
        // Legitimate editing must keep working for a user who does hold "versions" on the
        // document: the guard must not throw. Plain Document (not instanceof PageSnippet)
        // means the getLatestVersion() substitution is a no-op, so this covers the permission
        // gate itself without depending on the real Dao-backed magic method.
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(true);
        $document->method('getFullPath')->willReturn('/poc-published');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        $documentResolver->expects($this->once())->method('setDocument')->with($this->anything(), $document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn(true);

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader, $editmodeResolver);

        $request = new Request(['pimcore_editmode' => '1', 'pimcore_studio' => '1']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->dispatch($listener, $request);
    }

    public function testStudioPreviewOnPublishedDocumentIsDeniedWithoutVersionsPermission(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(false);
        $document->method('getFullPath')->willReturn('/poc-published');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader);

        $this->expectException(AccessDeniedHttpException::class);
        $this->dispatch($listener, new Request(['pimcore_studio_preview' => '1']));
    }

    public function testStudioPreviewOnPublishedDocumentServesLatestVersionWithVersionsPermission(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(true);
        $document->method('getFullPath')->willReturn('/poc-published');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        $documentResolver->expects($this->once())->method('setDocument')->with($this->anything(), $document);

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(new User());

        $listener = $this->makeListener($documentResolver, $this->adminRequestHelper(), $userLoader);

        $this->dispatch($listener, new Request(['pimcore_studio_preview' => '1']));
    }

    // -----------------------------------------------------------------------
    // isVersionAccessAllowedForDocument() - the guard that scopes ?pimcore_version=<id>
    // to the requested document and the "versions" permission.
    // -----------------------------------------------------------------------

    private function invokeIsVersionAccessAllowedForDocument(
        ?Version $version,
        Document $document,
        User $user
    ): bool {
        $method = new ReflectionMethod(ElementListener::class, 'isVersionAccessAllowedForDocument');

        $listener = new ElementListener(
            $this->createMock(DocumentResolver::class),
            $this->createMock(EditmodeResolver::class),
            $this->createMock(RequestHelper::class),
            $this->createMock(UserLoader::class)
        );

        return $method->invoke($listener, $version, $document, $user);
    }

    private function version(int $id, string $ctype, int $cid): Version
    {
        $version = new Version();
        $version->setId($id);
        $version->setCtype($ctype);
        $version->setCid($cid);

        return $version;
    }

    public function testVersionBelongingToAnotherDocumentIsRejected(): void
    {
        // The PoC: /poc-public?pimcore_version=2 where version 2 belongs to /poc-secret.
        // The requested document (id 1) must not be swapped out for a version of a
        // different document (id 2), no matter what the user is otherwise allowed to do.
        $requestedDocument = $this->createMock(Document::class);
        $requestedDocument->method('getId')->willReturn(1);
        $requestedDocument->method('isAllowed')->willReturn(true);

        $versionOfOtherDocument = $this->version(2, 'document', 2);

        $this->assertFalse(
            $this->invokeIsVersionAccessAllowedForDocument($versionOfOtherDocument, $requestedDocument, new User())
        );
    }

    public function testVersionOfNonDocumentElementIsRejected(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->willReturn(true);

        $objectVersion = $this->version(3, 'object', 1);

        $this->assertFalse(
            $this->invokeIsVersionAccessAllowedForDocument($objectVersion, $document, new User())
        );
    }

    public function testVersionOfSameDocumentIsRejectedWithoutVersionsPermission(): void
    {
        // Matches the PoC's "lowpriv" user: no document workspace, only the unrelated
        // "assets" permission - "versions" must not be implied by mere authentication.
        $document = $this->createMock(Document::class);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(false);

        $matchingVersion = $this->version(2, 'document', 1);

        $this->assertFalse(
            $this->invokeIsVersionAccessAllowedForDocument($matchingVersion, $document, new User())
        );
    }

    public function testVersionOfSameDocumentIsAllowedWithVersionsPermission(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->with('versions', $this->anything())->willReturn(true);

        $matchingVersion = $this->version(2, 'document', 1);

        $this->assertTrue(
            $this->invokeIsVersionAccessAllowedForDocument($matchingVersion, $document, new User())
        );
    }

    public function testMissingVersionIsRejected(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('getId')->willReturn(1);
        $document->method('isAllowed')->willReturn(true);

        $this->assertFalse(
            $this->invokeIsVersionAccessAllowedForDocument(null, $document, new User())
        );
    }
}
