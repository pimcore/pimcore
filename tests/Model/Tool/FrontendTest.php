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

namespace Pimcore\Tests\Model\Tool;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Frontend;

class FrontendTest extends ModelTestCase
{
    private Site $site1;

    private Site $site2;

    private Document\Page $testingDocument;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site1 = $this->createSite('site', 'example.com');
        $this->site2 = $this->createSite('site2', 'example2.com');
        $this->testingDocument = $this->createDocument('testing', $this->site2->getRootDocument()->getId());
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testIsDocumentInSite(): void
    {
        $this->assertFalse(
            Frontend::isDocumentInSite($this->site1, $this->testingDocument),
            'Test document should not be in site'
        );
        $this->assertTrue(
            Frontend::isDocumentInSite($this->site2, $this->testingDocument),
            'Test document should be in site'
        );

        $this->assertTrue(
            Frontend::isDocumentInSite($this->site2, $this->site2->getRootDocument()),
            'Root document should be in site'
        );
    }

    public function testSiteMappingIsRefreshedWhenSiteIsAdded(): void
    {
        $cacheWasEnabled = Cache::isEnabled();
        $handleCliWas = Cache::getHandler()->getHandleCli();
        Cache::enable();
        Cache::getHandler()->setHandleCli(true);
        Cache::setForceImmediateWrite(true);

        try {
            $root = $this->createDocument('mapping-root', 1);
            $child = $this->createDocument('child', $root->getId());

            RuntimeCache::clear();
            Cache::clearTag('system');
            Cache::getHandler()->removeClearedTags(['system', 'resource', 'site']);

            // warms the persistent site path mapping while the root is not yet a site
            $this->assertNull(Frontend::getSiteIdForDocument($child));

            $site = new Site();
            $site->setRootDocument($root);
            $site->setMainDomain('mapping.example.com');
            $site->setRootPath('/');
            $site->save();

            // the next request starts with an empty runtime cache and reads the persistent one
            RuntimeCache::clear();
            Cache::getHandler()->removeClearedTags(['system', 'resource', 'site']);

            $this->assertSame($site->getId(), Frontend::getSiteIdForDocument($child));
            $this->assertSame($site->getId(), Frontend::getSiteForDocument($child)?->getId());
        } finally {
            Cache::setForceImmediateWrite(false);
            Cache::getHandler()->setHandleCli($handleCliWas);
            if (!$cacheWasEnabled) {
                Cache::disable();
            }
            RuntimeCache::clear();
        }
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

    private function createSite(string $key, string $mainDomain): Site
    {
        $site = new Site();
        $site->setRootDocument($this->createDocument($key, 1));
        $site->setMainDomain($mainDomain);
        $site->setRootPath('/');
        $site->save();

        return $site;
    }
}
