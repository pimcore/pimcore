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

use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Class ServiceTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.document.service
 */
class ServiceTest extends ModelTestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    /**
     * Regression test: two published pages share the same pretty URL - one belongs to a site,
     * the other to a second site whose root document is nested inside the first site. Both
     * pages live below the first site's root path, so a plain path-prefix match finds both.
     * Resolving the pretty URL within the enclosing site must return the document that
     * actually belongs to it (nearest site root wins, consistent with
     * Tool\Frontend::getSiteIdForDocument()) - never the nested site's document.
     *
     * @see \Pimcore\Model\Document\Service\Dao::getDocumentIdByPrettyUrlInSite()
     */
    public function testPrettyUrlInSiteDoesNotResolveToDocumentOfNestedSite(): void
    {
        $prettyUrl = '/impressum-' . uniqid();

        $parentSiteRoot = $this->createPage('section-' . uniqid(), 1);
        $parentSite = $this->createSite($parentSiteRoot, 'parent-site-' . uniqid() . '.example.com');

        // the nested site root sorts alphabetically before "footer" and its pretty URL page is
        // created (and therefore id-ordered) before the parent site's page, so an
        // unfixed, unordered path-prefix lookup would pick the nested site's document
        $nestedSiteRoot = $this->createPage('aaa-sub-site-' . uniqid(), $parentSiteRoot->getId());
        $nestedSite = $this->createSite($nestedSiteRoot, 'nested-site-' . uniqid() . '.example.com');

        $nestedSitePage = $this->createPage(
            'impressum',
            $this->createFolderPath($nestedSiteRoot, ['footer', 'sockel'])->getId(),
            $prettyUrl
        );

        $parentSitePage = $this->createPage(
            'impressum',
            $this->createFolderPath($parentSiteRoot, ['footer', 'sockel'])->getId(),
            $prettyUrl
        );

        $dao = (new Service())->getDao();

        $this->assertSame(
            $parentSitePage->getId(),
            $dao->getDocumentIdByPrettyUrlInSite($parentSite, $prettyUrl),
            'The pretty URL requested within the parent site must resolve to the parent site\'s own document, not to the document of a site nested inside it.'
        );

        $this->assertSame(
            $nestedSitePage->getId(),
            $dao->getDocumentIdByPrettyUrlInSite($nestedSite, $prettyUrl),
            'The pretty URL requested within the nested site must resolve to the nested site\'s document.'
        );
    }

    /**
     * Regression test: two published pages share the same pretty URL and neither belongs to a
     * site. The global pretty URL lookup has to pick the same document on every request
     * instead of whichever row the storage engine happens to return first.
     *
     * @see \Pimcore\Model\Document\Dao::getByPrettyUrl()
     */
    public function testPrettyUrlWithoutSiteResolvesDeterministically(): void
    {
        $prettyUrl = '/imprint-' . uniqid();

        $firstPage = $this->createPage('imprint-a-' . uniqid(), 1, $prettyUrl);
        $secondPage = $this->createPage('imprint-b-' . uniqid(), 1, $prettyUrl);

        $this->assertLessThan(
            $secondPage->getId(),
            $firstPage->getId(),
            'The document created first is expected to carry the lower id.'
        );

        foreach ([1, 2] as $attempt) {
            $document = new Document();
            $document->getDao()->getByPrettyUrl($prettyUrl);

            $this->assertSame(
                $firstPage->getId(),
                $document->getId(),
                sprintf(
                    'A pretty URL shared by several documents must always resolve to the same document (attempt %d).',
                    $attempt
                )
            );
        }
    }

    /**
     * Regression test (pimcore/pimcore#17256): a site page and a main site page share the same pretty URL
     * and the site page has the lower id. A request without a site must resolve to the main site page.
     *
     * @see \Pimcore\Model\Document\Service\Dao::getDocumentIdByPrettyUrlOutsideSites()
     */
    public function testPrettyUrlOutsideSitesIgnoresDocumentsOfSites(): void
    {
        $prettyUrl = '/lorem-' . uniqid();

        $siteRoot = $this->createPage('site-' . uniqid(), 1);
        $this->createSite($siteRoot, 'site-' . uniqid() . '.example.com');

        $sitePage = $this->createPage('lorem', $siteRoot->getId(), $prettyUrl);
        $mainPage = $this->createPage('lorem-' . uniqid(), 1, $prettyUrl);

        $this->assertLessThan(
            $mainPage->getId(),
            $sitePage->getId(),
            'The site page is expected to carry the lower id.'
        );

        $dao = (new Service())->getDao();

        $this->assertSame(
            $mainPage->getId(),
            $dao->getDocumentIdByPrettyUrlOutsideSites($prettyUrl),
            'A request without a site must resolve the pretty URL to the main site page, not to the lower id page of a site.'
        );

        $this->assertSame(
            $mainPage->getId(),
            $dao->getDocumentIdByPrettyUrlOutsideSites($prettyUrl . '/'),
            'A trailing slash must not change the lookup.'
        );

        $this->assertSame(
            0,
            $dao->getDocumentIdByPrettyUrlOutsideSites('/unknown-' . uniqid()),
            'An unknown pretty URL must not resolve to a document.'
        );

        $onlySitePrettyUrl = '/only-site-' . uniqid();
        $this->createPage('only-site', $siteRoot->getId(), $onlySitePrettyUrl);
        $this->assertSame(
            0,
            $dao->getDocumentIdByPrettyUrlOutsideSites($onlySitePrettyUrl),
            'A pretty URL that exists only inside a site must not resolve outside of it.'
        );
    }

    private function createPage(string $key, int $parentId, ?string $prettyUrl = null): Page
    {
        $page = new Page();
        $page->setKey($key);
        $page->setParentId($parentId);
        $page->setPublished(true);
        $page->setUserOwner(1);
        $page->setUserModification(1);
        $page->setCreationDate(time());
        if ($prettyUrl !== null) {
            $page->setPrettyUrl($prettyUrl);
        }
        $page->save();

        return $page;
    }

    /**
     * @param string[] $folderKeys
     */
    private function createFolderPath(Document $parent, array $folderKeys): Document
    {
        foreach ($folderKeys as $folderKey) {
            $folder = new Document\Folder();
            $folder->setKey($folderKey);
            $folder->setParentId($parent->getId());
            $folder->setUserOwner(1);
            $folder->setUserModification(1);
            $folder->setCreationDate(time());
            $folder->save();

            $parent = $folder;
        }

        return $parent;
    }

    private function createSite(Page $rootDocument, string $mainDomain): Site
    {
        $site = new Site();
        $site->setRootDocument($rootDocument);
        $site->setMainDomain($mainDomain);
        $site->save();

        return $site;
    }
}
