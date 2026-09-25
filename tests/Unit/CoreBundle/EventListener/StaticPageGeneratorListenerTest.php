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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\StaticPageGeneratorListener;
use Pimcore\Config;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class StaticPageGeneratorListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setCurrentSite(null);

        parent::tearDown();
    }

    private function setCurrentSite(?Site $site): void
    {
        (new ReflectionProperty(Site::class, 'currentSite'))->setValue(null, $site);
    }

    private function enterSite(string $rootPath): void
    {
        $rootDocument = $this->createMock(Page::class);
        $rootDocument->method('getKey')->willReturn(basename($rootPath));

        $site = new Site();
        $site->setRootDocument($rootDocument);
        $site->setRootPath($rootPath);
        $this->setCurrentSite($site);
    }

    private function makePage(string $realFullPath, ?string $prettyUrl = null): Page
    {
        $page = $this->createMock(Page::class);
        $page->method('getRealFullPath')->willReturn($realFullPath);
        $page->method('getPrettyUrl')->willReturn($prettyUrl);
        $page->method('getStaticGeneratorEnabled')->willReturn(true);

        return $page;
    }

    /**
     * @return array{0: StaticPageGeneratorListener, 1: StaticPageGenerator&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function makeListener(?Page $document): array
    {
        $staticPageGenerator = $this->createMock(StaticPageGenerator::class);

        $documentResolver = $this->createMock(DocumentResolver::class);
        $documentResolver->method('getDocument')->willReturn($document);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);

        $listener = new StaticPageGeneratorListener(
            $staticPageGenerator,
            $documentResolver,
            $requestHelper,
            new Config()
        );

        $pimcoreContextResolver = $this->createMock(PimcoreContextResolver::class);
        $pimcoreContextResolver->method('matchesPimcoreContext')->willReturn(true);
        $listener->setPimcoreContextResolver($pimcoreContextResolver);

        $staticPageResolver = $this->createMock(StaticPageResolver::class);
        $staticPageResolver->method('hasStaticPageContext')->willReturn(false);
        $listener->setStaticPageResolver($staticPageResolver);

        return [$listener, $staticPageGenerator];
    }

    private function dispatchResponse(StaticPageGeneratorListener $listener, Request $request, Response $response): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $listener->onKernelResponse($event);
    }

    public function testGeneratesStaticPageWhenRequestPathMatchesResolvedDocument(): void
    {
        $document = $this->makePage('/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->once())
            ->method('generate')
            ->with($document, ['response' => 'body']);

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testGeneratesStaticPageWhenRequestPathMatchesDocumentPrettyUrl(): void
    {
        $document = $this->makePage('/en/products-real-path', '/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->once())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testSkipsGenerationWhenResolvedDocumentIsAFallbackAncestorOfTheRequestPath(): void
    {
        // DocumentFallbackListener resolves an unmatched sub-path (e.g. /products/foo) to the
        // nearest ancestor document (/products). The response rendered for the sub-path must
        // not be persisted under the ancestor's cache key.
        $document = $this->makePage('/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->never())->method('generate');

        $request = Request::create('/products/foo', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testSkipsGenerationWhenResponseStatusIsNotOk(): void
    {
        $document = $this->makePage('/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->never())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('not found', 404));
    }

    public function testSkipsGenerationWhenRequestHasAQueryString(): void
    {
        $document = $this->makePage('/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->never())->method('generate');

        $request = Request::create('/products?q=x', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testSkipsGenerationWhenDocumentHasStaticGeneratorDisabled(): void
    {
        $document = $this->createMock(Page::class);
        $document->method('getRealFullPath')->willReturn('/products');
        $document->method('getPrettyUrl')->willReturn(null);
        $document->method('getStaticGeneratorEnabled')->willReturn(false);

        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->never())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testGeneratesStaticPageForSiteRelativePrettyUrlInsideASite(): void
    {
        // Pretty URLs are site-relative and routed against the original request path
        // (DocumentRouteHandler::matchRequest), so they must not be compared with the
        // site-root-prefixed path.
        $this->enterSite('/site');

        $document = $this->makePage('/site/en/products-real-path', '/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->once())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testGeneratesStaticPageForRealPathInsideASite(): void
    {
        $this->enterSite('/site');

        $document = $this->makePage('/site/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->once())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testSkipsGenerationWhenSiteRelativeRealPathIsComparedWithoutSiteRoot(): void
    {
        // Inside a site, /products addresses /site/products, not a document at /products.
        $this->enterSite('/site');

        $document = $this->makePage('/products');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->never())->method('generate');

        $request = Request::create('/products', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }

    public function testGeneratesStaticPageForNestedSiteRootDocument(): void
    {
        // the site root document is not necessarily a top-level document
        $this->enterSite('/sites/de');

        $document = $this->makePage('/sites/de');
        [$listener, $staticPageGenerator] = $this->makeListener($document);

        $staticPageGenerator->expects($this->once())->method('generate');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->dispatchResponse($listener, $request, new Response('body', 200));
    }
}
