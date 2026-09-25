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

namespace Pimcore\Tests\Model\Routing;

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\RoutingListener;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Model\User;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Pimcore\Routing\Dynamic\DynamicRequestContext;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Authentication;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression test for GHSA-4f8h-wfx8-wwmx at the routing call sites: an admin query parameter
 * (e.g. ?pimcore_preview=1) must only bypass site resolution, site isolation and the main domain
 * redirect when it comes with an authenticated admin session. Each call site is covered in both
 * directions, so reverting it to the parameter-only isFrontendRequestByAdmin() check fails the
 * unauthenticated case, and a check that never passes fails the authenticated one.
 */
class AdminFrontendRequestSiteIsolationTest extends ModelTestCase
{
    private const ADMIN_PARAM = 'pimcore_preview';

    private const MAIN_DOMAIN = 'main-domain.site-isolation.test';

    private Site $site;

    private Document\Page $siteDocument;

    private ?User $user = null;

    private ?array $originalSystemSettings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetCurrentSite();

        $rootDocument = $this->createDocument('site-isolation-root-' . uniqid(), 1);
        $this->siteDocument = $this->createDocument('page', $rootDocument->getId());

        $this->site = new Site();
        $this->site->setRootDocument($rootDocument);
        $this->site->setMainDomain('site-' . uniqid() . '.site-isolation.test');
        $this->site->setRootPath($rootDocument->getRealFullPath());
        $this->site->save();

        // the site path mapping and domain lookups are memoized, drop them so the new site is seen
        Cache::clearAll();
        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        if (null !== $this->originalSystemSettings) {
            (new SystemSettingsConfig(...$this->systemSettingsConfigArguments()))->testSave($this->originalSystemSettings);
            $this->originalSystemSettings = null;
        }

        $this->user?->delete();
        $this->user = null;

        $this->resetCurrentSite();

        parent::tearDown();
    }

    public function testAuthenticatedHelperAcceptsAdminParamWithValidAdminSession(): void
    {
        $request = $this->createAdminRequest('http://example.com/some/page', true);

        $this->assertTrue($this->createRequestHelper($request)->isAuthenticatedFrontendRequestByAdmin($request));
    }

    public function testSiteIsResolvedForAdminParamWithoutSession(): void
    {
        $request = $this->createAdminRequest('http://' . $this->site->getMainDomain() . '/page', false);

        $this->dispatchRoutingListener($request);

        $this->assertSame(
            $this->site->getId(),
            $this->createSiteResolver($request)->getSite($request)?->getId(),
            'A spoofed admin parameter must not disable site resolution.'
        );
    }

    public function testSiteIsNotResolvedForAuthenticatedAdminRequest(): void
    {
        $request = $this->createAdminRequest('http://' . $this->site->getMainDomain() . '/page', true);

        $this->dispatchRoutingListener($request);

        $this->assertNull($this->createSiteResolver($request)->getSite($request));
    }

    public function testMainDomainRedirectAppliesToAdminParamWithoutSession(): void
    {
        $this->enableRedirectToMainDomain();
        $request = $this->createAdminRequest('http://other-domain.site-isolation.test/page', false);

        $event = $this->dispatchRoutingListener($request);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame(self::MAIN_DOMAIN, parse_url($event->getResponse()->getTargetUrl(), PHP_URL_HOST));
    }

    public function testMainDomainRedirectIsSkippedForAuthenticatedAdminRequest(): void
    {
        $this->enableRedirectToMainDomain();
        $request = $this->createAdminRequest('http://other-domain.site-isolation.test/page', true);

        $event = $this->dispatchRoutingListener($request);

        $this->assertNull($event->getResponse());
    }

    public function testSiteDocumentIsNotRoutedOutsideItsSiteForAdminParamWithoutSession(): void
    {
        $request = $this->createAdminRequest('http://example.com' . $this->siteDocument->getRealFullPath(), false);

        $this->expectException(NotFoundHttpException::class);

        $this->matchDocumentRoute($request);
    }

    public function testSiteDocumentIsRoutedOutsideItsSiteForAuthenticatedAdminRequest(): void
    {
        $request = $this->createAdminRequest('http://example.com' . $this->siteDocument->getRealFullPath(), true);

        $collection = $this->matchDocumentRoute($request);

        $this->assertCount(1, $collection);
    }

    private function createAdminRequest(string $uri, bool $authenticated): Request
    {
        $request = Request::create($uri, 'GET', [self::ADMIN_PARAM => '1']);

        if ($authenticated) {
            $session = new Session(new MockArraySessionStorage());
            $session->start();
            $session->set(
                '_security_pimcore_admin',
                serialize(new UsernamePasswordToken(new SecurityUser($this->createAdminUser()), 'pimcore_admin'))
            );

            $request->setSession($session);
            $request->cookies->set($session->getName(), $session->getId());
        }

        return $request;
    }

    private function createRequestHelper(Request $request): RequestHelper
    {
        return new RequestHelper($this->createRequestStack($request), new RequestContext());
    }

    private function createSiteResolver(Request $request): SiteResolver
    {
        return new SiteResolver($this->createRequestStack($request));
    }

    private function createRequestStack(Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    private function dispatchRoutingListener(Request $request): RequestEvent
    {
        $contextResolver = Pimcore::getContainer()->get(PimcoreContextResolver::class);
        $contextResolver->setPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT);

        $listener = new RoutingListener(
            $this->createRequestHelper($request),
            $this->createSiteResolver($request),
            new Config()
        );
        $listener->setPimcoreContextResolver($contextResolver);
        $listener->setLogger(new NullLogger());

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
        $this->runWithGlobalRequest($request, fn () => $listener->onKernelRequest($event));

        return $event;
    }

    private function matchDocumentRoute(Request $request): RouteCollection
    {
        $handler = new DocumentRouteHandler(
            new Document\Service(),
            $this->createSiteResolver($request),
            $this->createRequestHelper($request),
            new Config(),
            new StaticPageResolver($this->createRequestStack($request))
        );

        $path = $request->getPathInfo();
        $collection = new RouteCollection();
        $this->runWithGlobalRequest(
            $request,
            fn () => $handler->matchRequest($collection, new DynamicRequestContext($request, $path, $path))
        );

        return $collection;
    }

    /**
     * Static helpers down the call chain (e.g. the anonymized client IP in the redirect log, or
     * Tool::isFrontendRequestByAdmin()) read the request from the container's request stack.
     */
    private function runWithGlobalRequest(Request $request, callable $callback): void
    {
        $requestStack = Pimcore::getContainer()->get('request_stack');
        $requestStack->push($request);

        try {
            $callback();
        } finally {
            $requestStack->pop();
        }
    }

    private function enableRedirectToMainDomain(): void
    {
        $systemSettings = SystemSettingsConfig::get();
        unset($systemSettings['writeable']);
        $this->originalSystemSettings = $systemSettings;

        $systemSettings['general']['redirect_to_maindomain'] = true;
        $systemSettings['general']['domain'] = self::MAIN_DOMAIN;
        (new SystemSettingsConfig(...$this->systemSettingsConfigArguments()))->testSave($systemSettings);
    }

    /**
     * @return array<int, object>
     */
    private function systemSettingsConfigArguments(): array
    {
        return [
            Pimcore::getContainer()->get('event_dispatcher'),
            Pimcore::getContainer()->get(LocaleServiceInterface::class),
        ];
    }

    private function createAdminUser(): User
    {
        if (null === $this->user) {
            $name = 'site_isolation_admin_' . uniqid();

            $user = new User();
            $user->setParentId(0);
            $user->setName($name);
            $user->setPassword(Authentication::getPasswordHash($name, 'password'));
            $user->setActive(true);
            $user->setAdmin(true);
            $user->save();

            $this->user = $user;
        }

        return $this->user;
    }

    private function createDocument(string $key, int $parentId): Document\Page
    {
        $document = new Document\Page();
        $document->setKey($key);
        $document->setPublished(true);
        $document->setParentId($parentId);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->save();

        return $document;
    }

    private function resetCurrentSite(): void
    {
        (new ReflectionProperty(Site::class, 'currentSite'))->setValue(null, null);
    }
}
