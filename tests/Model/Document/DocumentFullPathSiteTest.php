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

namespace Pimcore\Tests\Model\Document;

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\HardlinkCanonicalListener;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for pimcore/pimcore#17180: a link to a document of a site, rendered in a frontend
 * request outside of any site (main domain), has to point to the site domain. The real path of the
 * document does not resolve on the main domain.
 */
class DocumentFullPathSiteTest extends ModelTestCase
{
    private Site $site;

    private Document\Page $siteRoot;

    private Document\Page $sitePage;

    private Document\Page $mainPage;

    private ?Request $request = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setCurrentSite(null);

        $this->siteRoot = $this->createPage('fullpath-site-' . uniqid(), 1);
        $this->sitePage = $this->createPage('page', $this->siteRoot->getId());
        $this->mainPage = $this->createPage('fullpath-main-' . uniqid(), 1);

        $this->site = new Site();
        $this->site->setRootDocument($this->siteRoot);
        $this->site->setMainDomain('site-' . uniqid() . '.fullpath.test');
        $this->site->setRootPath($this->siteRoot->getRealFullPath());
        $this->site->save();

        // the site path mapping is memoized, drop it so the new site is seen
        Cache::clearAll();
        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        $this->popRequest();
        $this->setCurrentSite(null);

        parent::tearDown();
    }

    public function testLinkToSiteDocumentFromMainDomainContainsSiteDomain(): void
    {
        $this->pushRequest('http://main.fullpath.test/some/page');

        $this->assertSame(
            'http://' . $this->site->getMainDomain() . '/page',
            $this->freshDocument($this->sitePage)->getFullPath()
        );
    }

    public function testLinkToSiteRootFromMainDomainContainsSiteDomain(): void
    {
        $this->pushRequest('http://main.fullpath.test/some/page');

        $this->assertSame(
            'http://' . $this->site->getMainDomain() . '/',
            $this->freshDocument($this->siteRoot)->getFullPath()
        );
    }

    public function testLinkToMainSiteDocumentFromMainDomainStaysRelative(): void
    {
        $this->pushRequest('http://main.fullpath.test/some/page');

        $this->assertSame(
            $this->mainPage->getRealFullPath(),
            $this->freshDocument($this->mainPage)->getFullPath()
        );
    }

    public function testLinkToSiteDocumentInsideItsSiteStaysRelative(): void
    {
        $this->pushRequest('http://' . $this->site->getMainDomain() . '/page');
        $this->setCurrentSite($this->site);

        $this->assertSame('/page', $this->freshDocument($this->sitePage)->getFullPath());
    }

    public function testHardlinkAsMainDocumentOnMainDomainDoesNotNeedACurrentSite(): void
    {
        $this->pushRequest('http://main.fullpath.test/hl');

        $hardlink = new Document\Hardlink();
        $wrapper = new Document\Hardlink\Wrapper\Page();
        $wrapper->setHardLinkSource($hardlink);
        $wrapper->setSourceDocument($this->sitePage);
        $this->request->attributes->set(DynamicRouter::CONTENT_KEY, $wrapper);

        $this->assertSame(
            'http://' . $this->site->getMainDomain() . '/page',
            $this->freshDocument($this->sitePage)->getFullPath()
        );
    }

    public function testCanonicalHeaderOfHardlinkOnMainDomainHasASingleHost(): void
    {
        $this->pushRequest('http://main.fullpath.test/hl');
        $response = new Response();

        $listener = new HardlinkCanonicalListener(new Pimcore\Http\Request\Resolver\DocumentResolver(
            Pimcore::getContainer()->get('request_stack')
        ));
        $handleHardlink = new ReflectionMethod($listener, 'handleHardlink');
        $handleHardlink->invoke($listener, $this->request, $response, $this->sitePage);

        $this->assertSame(
            '<http://' . $this->site->getMainDomain() . '/page>; rel="canonical"',
            $response->headers->get('Link')
        );
    }

    public function testSiteRootPathWithRegexCharactersIsQuoted(): void
    {
        $root = $this->createPage('shop(' . uniqid(), 1);
        $page = $this->createPage('page', $root->getId());

        $site = new Site();
        $site->setRootDocument($root);
        $site->setMainDomain('shop-' . uniqid() . '.fullpath.test');
        $site->setRootPath($root->getRealFullPath());
        $site->save();
        Cache::clearAll();
        RuntimeCache::clear();

        $this->pushRequest('http://main.fullpath.test/some/page');

        $this->assertSame(
            'http://' . $site->getMainDomain() . '/page',
            $this->freshDocument($page)->getFullPath()
        );
    }

    private function createPage(string $key, int $parentId): Document\Page
    {
        $page = new Document\Page();
        $page->setKey($key);
        $page->setParentId($parentId);
        $page->setPublished(true);
        $page->save();

        return $page;
    }

    // getFullPath() memoizes its result per instance as soon as a main request exists
    private function freshDocument(Document $document): Document
    {
        RuntimeCache::clear();

        return Document::getById($document->getId(), ['force' => true]);
    }

    private function pushRequest(string $uri): void
    {
        $this->request = Request::create($uri);
        Pimcore::getContainer()->get('request_stack')->push($this->request);
    }

    private function popRequest(): void
    {
        if (null !== $this->request) {
            Pimcore::getContainer()->get('request_stack')->pop();
            $this->request = null;
        }
    }

    private function setCurrentSite(?Site $site): void
    {
        $property = new ReflectionProperty(Site::class, 'currentSite');
        $property->setValue(null, $site);
    }
}
