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

use ArrayObject;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Security\User\UserLoader;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Serialize;
use Psr\Log\NullLogger;
use ReflectionClass;
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

    /**
     * Runs a studio preview request for object 2 and returns the object that ended up in the runtime cache.
     * A denied request must leave the cache untouched (checked before the cleanup, then rethrown).
     *
     * @param DataObject\Concrete|null $persisted the element as stored, authorization is evaluated on it
     * @param DataObject\Concrete|null $draft the latest version, defaults to the persisted element when there is none
     */
    private function runObjectStudioPreview(?DataObject\Concrete $persisted, ?DataObject\Concrete $draft = null): ?DataObject\Concrete
    {
        $user = new User();
        $user->setId(42);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn(null);

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(true);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));
        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn($user);
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader, $persisted, $draft) extends ElementListener {
            public function __construct(
                DocumentResolver $documentResolver,
                EditmodeResolver $editmodeResolver,
                RequestHelper $requestHelper,
                UserLoader $userLoader,
                private ?DataObject\Concrete $persisted,
                private ?DataObject\Concrete $draft
            ) {
                parent::__construct($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
            }

            protected function getPersistedObject(int $id): ?DataObject\Concrete
            {
                return $this->persisted;
            }

            protected function getLatestVersion(DataObject\Concrete $object, UserInterface $user): DataObject\Concrete
            {
                return $this->draft ?? $object;
            }
        };
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        $request = Request::create('/page', 'GET', ['pimcore_studio_preview' => '1', 'pimcore_object_preview' => '2']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        RuntimeCache::getInstance()->offsetUnset('object_2');

        try {
            try {
                $listener->onKernelController($this->makeControllerEvent($request));
            } catch (AccessDeniedHttpException $exception) {
                $this->assertFalse(RuntimeCache::isRegistered('object_2'), 'a denied preview must not be cached');

                throw $exception;
            }

            return RuntimeCache::isRegistered('object_2') ? RuntimeCache::get('object_2') : null;
        } finally {
            RuntimeCache::getInstance()->offsetUnset('object_2');
        }
    }

    /**
     * @param string[] $allowedPermissions
     */
    private function makeObject(array $allowedPermissions): DataObject\Concrete
    {
        $object = $this->createMock(DataObject\Concrete::class);
        $object->method('isAllowed')->willReturnCallback(
            static fn (string $type): bool => in_array($type, $allowedPermissions, true)
        );
        $object->method('getId')->willReturn(2);
        $object->method('getFullPath')->willReturn('/objects/preview');

        return $object;
    }

    public function testObjectStudioPreviewIsDeniedWithoutViewPermission(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->runObjectStudioPreview($this->makeObject([]));
    }

    public function testObjectStudioPreviewIsDeniedWithOnlyVersionsPermission(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->runObjectStudioPreview($this->makeObject(['versions']));
    }

    public function testObjectStudioPreviewIsDeniedWhenTheDraftIsViewableButThePersistedObjectIsNot(): void
    {
        // a draft can carry another parent than the stored element and thereby other workspace grants
        $persisted = $this->makeObject([]);
        $draft = $this->makeObject(['view', 'versions']);

        $this->expectException(AccessDeniedHttpException::class);
        $this->runObjectStudioPreview($persisted, $draft);
    }

    public function testObjectStudioPreviewWithoutVersionsPermissionServesThePublishedObject(): void
    {
        $persisted = $this->makeObject(['view']);
        $draft = $this->makeObject(['view', 'versions']);

        $this->assertNull($this->runObjectStudioPreview($persisted, $draft), 'no draft may be substituted');
    }

    public function testObjectStudioPreviewCachesTheDraftWithViewAndVersionsPermission(): void
    {
        $persisted = $this->makeObject(['view', 'versions']);
        $draft = $this->makeObject([]);

        $this->assertSame($draft, $this->runObjectStudioPreview($persisted, $draft));
    }

    public function testObjectStudioPreviewCachesThePersistedObjectWhenThereIsNoDraft(): void
    {
        $persisted = $this->makeObject(['view', 'versions']);

        $this->assertSame($persisted, $this->runObjectStudioPreview($persisted));
    }

    public function testObjectStudioPreviewWithoutResolvableObjectCachesNothing(): void
    {
        $this->assertNull($this->runObjectStudioPreview(null));
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
                protected function handleObjectParams(Request $request, User $user): void
                {
                }
            }
        : ElementListener::class;
        $listener = $stubObjectParams ? $class : new $class($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        return $listener;
    }

    private function makeAnonymousListenerWithVersion(Version $version, DocumentResolver $documentResolver): ElementListener
    {
        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));
        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn(null);
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader, $version) extends ElementListener {
            public function __construct(
                DocumentResolver $documentResolver,
                EditmodeResolver $editmodeResolver,
                RequestHelper $requestHelper,
                UserLoader $userLoader,
                private Version $version
            ) {
                parent::__construct($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
            }

            protected function loadVersion(int $versionId): Version
            {
                return $this->version;
            }
        };
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        return $listener;
    }

    /**
     * The `?v=` public-version preview must only substitute a version that belongs to the
     * requested document, even when the version's `public` flag is set.
     */
    public function testPublicVersionOfForeignDocumentIsNotRendered(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $foreignDocument = $this->createMock(Document::class);
        $version = (new Version())->setCtype('document')->setCid(11)->setPublic(true)->setData($foreignDocument);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        // the original, route-resolved document must be kept, never the foreign version's data
        $documentResolver->expects($this->once())->method('setDocument')
            ->with($this->anything(), $this->identicalTo($routeDocument));

        $listener = $this->makeAnonymousListenerWithVersion($version, $documentResolver);

        $request = Request::create('/page', 'GET', ['v' => '42']);
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    public function testPublicVersionOfNonDocumentElementIsNotRendered(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $foreignData = $this->createMock(Document::class);
        $version = (new Version())->setCtype('object')->setCid(10)->setPublic(true)->setData($foreignData);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->once())->method('setDocument')
            ->with($this->anything(), $this->identicalTo($routeDocument));

        $listener = $this->makeAnonymousListenerWithVersion($version, $documentResolver);

        $request = Request::create('/page', 'GET', ['v' => '42']);
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    public function testPublicVersionOfSameDocumentIsRendered(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $versionDocument = $this->createMock(Document::class);
        $versionDocument->method('getFullPath')->willReturn('/page');
        $version = (new Version())->setCtype('document')->setCid(10)->setPublic(true)->setData($versionDocument);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->once())->method('setDocument')
            ->with($this->anything(), $this->identicalTo($versionDocument));

        $listener = $this->makeAnonymousListenerWithVersion($version, $documentResolver);

        $request = Request::create('/page', 'GET', ['v' => '42']);
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    public function testNonPublicVersionOfSameDocumentIsNotRendered(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $versionDocument = $this->createMock(Document::class);
        $version = (new Version())->setCtype('document')->setCid(10)->setPublic(false)->setData($versionDocument);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->once())->method('setDocument')
            ->with($this->anything(), $this->identicalTo($routeDocument));

        $listener = $this->makeAnonymousListenerWithVersion($version, $documentResolver);

        $request = Request::create('/page', 'GET', ['v' => '42']);
        $listener->onKernelController($this->makeControllerEvent($request));
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

            protected function handleObjectParams(Request $request, User $user): void
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

    /**
     * @param string[] $allowedPermissions
     */
    private function runWithVersionParam(Document $routeDocument, Version $version, DocumentResolver $documentResolver, array $allowedPermissions = ['view', 'versions']): void
    {
        $user = new User();
        $user->setId(42);
        $routeDocument->method('isAllowed')->willReturnCallback(
            static fn (string $type): bool => in_array($type, $allowedPermissions, true)
        );

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(true);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));
        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn($user);
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader, $version) extends ElementListener {
            public function __construct(
                DocumentResolver $documentResolver,
                EditmodeResolver $editmodeResolver,
                RequestHelper $requestHelper,
                UserLoader $userLoader,
                private Version $version
            ) {
                parent::__construct($documentResolver, $editmodeResolver, $requestHelper, $userLoader);
            }

            protected function loadVersion(int $versionId): ?Version
            {
                return $this->version;
            }

            protected function handleObjectParams(Request $request, User $user): void
            {
            }
        };
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        $request = Request::create('/page', 'GET', ['pimcore_version' => '5']);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $listener->onKernelController($this->makeControllerEvent($request));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function foreignVersionProvider(): array
    {
        return [
            'version of another document' => ['document', 11],
            'version of a non-document element' => ['object', 10],
        ];
    }

    /**
     * @dataProvider foreignVersionProvider
     */
    public function testPimcoreVersionOfForeignElementIsRejected(string $ctype, int $cid): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $foreignDocument = $this->createMock(Document::class);
        $version = (new Version())->setCtype($ctype)->setCid($cid)->setData($foreignDocument);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->never())->method('setDocument');

        $this->expectException(AccessDeniedHttpException::class);
        $this->runWithVersionParam($routeDocument, $version, $documentResolver);
    }

    public function testPimcoreVersionOfSameDocumentIsLoaded(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $versionDocument = $this->createMock(Document::class);
        $versionDocument->method('getFullPath')->willReturn('/page');
        $version = (new Version())->setCtype('document')->setCid(10)->setData($versionDocument);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->once())->method('setDocument')
            ->with($this->anything(), $this->identicalTo($versionDocument));

        $this->runWithVersionParam($routeDocument, $version, $documentResolver);
    }

    public function testPimcoreVersionOfSameDocumentIsRejectedWithoutVersionsPermission(): void
    {
        $routeDocument = $this->createMock(Document::class);
        $routeDocument->method('isPublished')->willReturn(true);
        $routeDocument->method('getId')->willReturn(10);
        $routeDocument->method('getFullPath')->willReturn('/page');

        $version = (new Version())->setCtype('document')->setCid(10)->setData($this->createMock(Document::class));

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($routeDocument);
        $documentResolver->expects($this->never())->method('setDocument');

        $this->expectException(AccessDeniedHttpException::class);
        $this->runWithVersionParam($routeDocument, $version, $documentResolver, ['view']);
    }

    /**
     * A page double whose draft lookup is recorded: Document::getLatestVersion() is a magic dao call,
     * which PHPUnit cannot configure on a mock.
     *
     * @param string[] $allowedPermissions
     * @param ArrayObject<int, mixed> $lookups receives the user ids the latest version is requested for
     */
    private function createPage(array $allowedPermissions, ArrayObject $lookups, ?Version $latestVersion = null): Page
    {
        $page = new class($allowedPermissions, $latestVersion, $lookups) extends Page {
            /**
             * @param string[] $allowedPermissions
             * @param ArrayObject<int, mixed> $lookups
             */
            public function __construct(private array $allowedPermissions, private ?Version $latestVersion, private ArrayObject $lookups)
            {
                parent::__construct();
            }

            public function isAllowed(string $type, ?User $user = null): bool
            {
                return in_array($type, $this->allowedPermissions, true);
            }

            public function getLatestVersion(?int $userId = null, bool $includingPublished = false): ?Version
            {
                $this->lookups[] = $userId;

                return $this->latestVersion;
            }
        };
        $page->setId(10);
        $page->setKey('page');
        $page->setPath('/');
        $page->setPublished(true);

        return $page;
    }

    /**
     * A draft whose payload is a different page, loaded through the regular Version::loadData() path.
     */
    private function createDraftVersion(): Version
    {
        $draft = new Page();
        $draft->setId(10);
        $draft->setKey('draft');
        $draft->setPath('/');

        $storage = $this->createMock(VersionStorageAdapterInterface::class);
        $storage->method('loadMetaData')->willReturn(Serialize::serialize($draft));

        $reflection = new ReflectionClass(Version::class);
        /** @var Version $version */
        $version = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('storageAdapter')->setValue($version, $storage);
        $version->setSerialized(true);
        $version->setCtype('document');
        $version->setCid(10);

        return $version;
    }

    /**
     * Runs the request through the listener as a backend user (id 42) and returns the document that was
     * handed to the resolver.
     *
     * @param array<string, string> $query
     */
    private function dispatchAdminRequest(Page $document, array $query, bool $editmode): Document
    {
        $user = new User();
        $user->setId(42);

        $resolved = null;
        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);
        $documentResolver->expects($this->once())->method('setDocument')
            ->willReturnCallback(static function (Request $request, Document $doc) use (&$resolved): void {
                $resolved = $doc;
            });

        $editmodeResolver = $this->createMock(EditmodeResolver::class);
        $editmodeResolver->method('isEditmode')->willReturn($editmode);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(true);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/'));

        $userLoader = $this->createMock(UserLoader::class);
        $userLoader->method('getUser')->willReturn($user);

        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new class($documentResolver, $editmodeResolver, $requestHelper, $userLoader) extends ElementListener {
            protected function handleObjectParams(Request $request, UserInterface $user): void
            {
            }
        };
        $listener->setLogger(new NullLogger());
        $listener->setPimcoreContextResolver($contextResolver);

        $request = Request::create('/page', 'GET', $query);
        $request->setSession(new Session(new MockArraySessionStorage()));

        // Version::loadData() would otherwise restore key and path from a persisted element with the same id
        $restoreKeyAndPath = DataObject\AbstractObject::doNotRestoreKeyAndPath();
        DataObject\AbstractObject::setDoNotRestoreKeyAndPath(true);

        try {
            $listener->onKernelController($this->makeControllerEvent($request));
        } finally {
            DataObject\AbstractObject::setDoNotRestoreKeyAndPath($restoreKeyAndPath);
        }

        $this->assertInstanceOf(Document::class, $resolved);

        return $resolved;
    }

    public function testEditmodeKeepsDocumentWithoutVersionsPermission(): void
    {
        $lookups = new ArrayObject();
        $document = $this->createPage(['view'], $lookups, $this->createDraftVersion());

        // pimcore_studio skips the session lookup, isolating the draft substitution under test
        $resolved = $this->dispatchAdminRequest($document, ['pimcore_studio' => '1'], true);

        $this->assertSame($document, $resolved);
        $this->assertSame('page', $resolved->getKey());
        $this->assertSame([], $lookups->getArrayCopy());
    }

    public function testEditmodeSubstitutesLatestVersionWithVersionsPermission(): void
    {
        $lookups = new ArrayObject();
        $document = $this->createPage(['view', 'versions'], $lookups, $this->createDraftVersion());

        $resolved = $this->dispatchAdminRequest($document, ['pimcore_studio' => '1'], true);

        $this->assertSame('draft', $resolved->getKey());
        $this->assertSame([42], $lookups->getArrayCopy());
    }

    public function testStudioPreviewKeepsDocumentWithoutVersionsPermission(): void
    {
        $lookups = new ArrayObject();
        $document = $this->createPage(['view'], $lookups, $this->createDraftVersion());

        $resolved = $this->dispatchAdminRequest($document, ['pimcore_studio_preview' => '1'], false);

        $this->assertSame($document, $resolved);
        $this->assertSame('page', $resolved->getKey());
        $this->assertSame([], $lookups->getArrayCopy());
    }

    public function testStudioPreviewSubstitutesLatestVersionWithVersionsPermission(): void
    {
        $lookups = new ArrayObject();
        $document = $this->createPage(['view', 'versions'], $lookups, $this->createDraftVersion());

        $resolved = $this->dispatchAdminRequest($document, ['pimcore_studio_preview' => '1'], false);

        $this->assertSame('draft', $resolved->getKey());
        $this->assertSame([42], $lookups->getArrayCopy());
    }
}
