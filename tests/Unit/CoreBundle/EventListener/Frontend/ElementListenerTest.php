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
use Pimcore\Security\User\UserLoader;
use Pimcore\Model\UserInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Regression coverage for GHSA-gqpp-f736-v4jh: the unpublished-document gate in
 * onKernelController() must consult the document's workspace permission (isAllowed('view', $user))
 * rather than merely the presence of a backend session user.
 */
class ElementListenerTest extends TestCase
{
    private function makeControllerEvent(Request $request, bool $mainRequest = true): ControllerEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = static fn () => null;

        return new ControllerEvent(
            $kernel,
            $controller,
            $request,
            $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }

    private function makeListener(?Document $document, bool $adminRequest, ?User $user, bool $stubObjectParams = false): ElementListener
    {
        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn(false);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn($adminRequest);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn($user);

        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        // handleObjectParams() performs a DB lookup unrelated to the permission gate under test
        $class = $stubObjectParams
            ? new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader) extends ElementListener {
                protected function handleObjectParams(Request $request, UserInterface $user): void
                {
                }
            }
            : ElementListener::class;
        $listener = $stubObjectParams ? $class : new $class($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        return $listener;
    }

    public function testBackendUserWithoutViewPermissionIsDeniedOnUnpublishedDocument(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(false);
        $document->method('getFullPath')->willReturn('/secret_unpublished');

        $user = new User();
        $user->setId(99);

        // The core assertion: isAllowed('view', $user) must actually be consulted, with the
        // real session user, and must be able to deny access on its own merits.
        $document->expects($this->once())
            ->method('isAllowed')
            ->with('view', $user)
            ->willReturn(false);

        $listener = $this->makeListener($document, adminRequest: true, user: $user);
        $request = Request::create('/secret_unpublished', 'GET', ['pimcore_preview' => '1']);

        $this->expectException(AccessDeniedHttpException::class);
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    public function testAnonymousRequestIsDeniedOnUnpublishedDocument(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(false);
        $document->method('getFullPath')->willReturn('/secret_unpublished');

        // No session user at all: isAllowed() must not even be consulted (short-circuited).
        $document->expects($this->never())->method('isAllowed');

        $listener = $this->makeListener($document, adminRequest: false, user: null);
        $request = Request::create('/secret_unpublished');

        $this->expectException(AccessDeniedHttpException::class);
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    public function testAnonymousRequestToPublishedDocumentIsNotDenied(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->expects($this->never())->method('isAllowed');

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        $documentResolver->expects($this->once())->method('setDocument');

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));
        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(null);
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new ElementListener($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        $request = Request::create('/some/page');
        $listener->onKernelController($this->makeControllerEvent($request));

        // No exception means legitimate anonymous browsing of published content still works.
        $this->addToAssertionCount(1);
    }

    public function testBackendUserWithViewPermissionIsNotDeniedOnUnpublishedDocument(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(false);
        $document->method('getFullPath')->willReturn('/secret_unpublished');

        $user = new User();
        $user->setId(42);

        $document->expects($this->once())
            ->method('isAllowed')
            ->with('view', $user)
            ->willReturn(true);

        $listener = $this->makeListener($document, adminRequest: true, user: $user, stubObjectParams: true);

        $request = Request::create('/secret_unpublished');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $listener->onKernelController($this->makeControllerEvent($request));

        // No exception means the view-permission gate let the permitted user through.
        $this->addToAssertionCount(1);
    }

    public function testBackendUserWithoutViewPermissionCannotUseAdminParamsOnPublishedDocument(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getFullPath')->willReturn('/published');

        $user = new User();
        $user->setId(99);

        $document->expects($this->once())
            ->method('isAllowed')
            ->with('view', $user)
            ->willReturn(false);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        // the original, route-resolved document must be kept untouched
        $documentResolver->expects($this->once())->method('setDocument')->with($this->anything(), $this->identicalTo($document));

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn(true);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(true);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn($user);

        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader) extends ElementListener {
            public bool $adminHandlerInvoked = false;

            protected function handleEditmode(Document $document, User $user, SessionInterface $session, bool $isPimcoreStudio): Document
            {
                $this->adminHandlerInvoked = true;

                return $document;
            }

            protected function handleObjectParams(Request $request, UserInterface $user): void
            {
            }
        };
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        $request = Request::create('/published', 'GET', ['pimcore_preview' => '1', 'pimcore_version' => '123']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $listener->onKernelController($this->makeControllerEvent($request));

        $this->assertFalse($listener->adminHandlerInvoked, 'Admin preview/editmode/version handling must be skipped without view permission');
    }
}
