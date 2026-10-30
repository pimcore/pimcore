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
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;

/**
 * Regression test for pimcore/pimcore#17180: a link to a document of a site, rendered in a frontend
 * request outside of any site (main domain), has to point to the site domain, the real path of the
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
