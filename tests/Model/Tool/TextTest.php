<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\Tool;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Text;

class TextTest extends ModelTestCase
{
    private Document\Page $testingDocument;

    protected function setUp(): void
    {
        parent::setUp();

        $site1 = $this->createSite('site', 'example.com');
        $site2 = $this->createSite('site2', 'example2.com');
        $this->testingDocument = $this->createDocument('testing', $site2->getRootDocument()->getId());
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testWysiwygText(): void
    {
        RuntimeCache::clear();

        $text = sprintf(
            'Link to a document <a href="%s" pimcore_id="%s" pimcore_type="document">The link</a>',
            $this->testingDocument->getFullPath(),
            $this->testingDocument->getId()
        );
        $expected = sprintf(
            'Link to a document <a href="http://example2.com/testing" pimcore_id="%s" pimcore_type="document">The link</a>',
            $this->testingDocument->getId()
        );

        $this->assertEquals($expected, Text::wysiwygText($text));
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
