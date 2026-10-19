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

    public function testWysiwygTextWithBrokenLinkAndExtraAttributes(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = $this->createDocument('unpublished-testing', $this->testingDocument->getParentId(), false);

        $text = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener">Link text</a>',
            $unpublishedDocument->getId()
        );
        $expected = 'Link to a document Link text';

        $this->assertEquals($expected, Text::wysiwygText($text));
    }

    public function testWysiwygTextWithBrokenLinkAndNestedMarkup(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = $this->createDocument('unpublished-testing-nested', $this->testingDocument->getParentId(), false);

        $text = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener"><strong>Link text</strong></a>',
            $unpublishedDocument->getId()
        );
        $expected = 'Link to a document <strong>Link text</strong>';

        $this->assertEquals($expected, Text::wysiwygText($text));
    }

    public function testWysiwygTextWithBrokenLinkAndEmptyLabel(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = $this->createDocument('unpublished-testing-empty', $this->testingDocument->getParentId(), false);

        $text = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener"></a>',
            $unpublishedDocument->getId()
        );
        $expected = 'Link to a document ';

        $this->assertEquals($expected, Text::wysiwygText($text));
    }

    public function testWysiwygTextKeepsLinkToUnpublishedDocumentWhenRequested(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = $this->createDocument('unpublished-testing-keep', $this->testingDocument->getParentId(), false);

        $text = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener">Link text</a>',
            $unpublishedDocument->getId()
        );

        $this->assertEquals($text, Text::wysiwygTextKeepingLinks($text));
        $this->assertEquals('Link to a document Link text', Text::wysiwygText($text));
    }

    public function testWysiwygTextStillRewritesPublishedLinkWhenKeepingUnpublished(): void
    {
        RuntimeCache::clear();

        $text = sprintf(
            'Link to a document <a href="/old" pimcore_id="%s" pimcore_type="document">The link</a>',
            $this->testingDocument->getId()
        );
        $expected = sprintf(
            'Link to a document <a href="http://example2.com/testing" pimcore_id="%s" pimcore_type="document">The link</a>',
            $this->testingDocument->getId()
        );

        $this->assertEquals($expected, Text::wysiwygTextKeepingLinks($text));
    }

    public function testWysiwygTextStillRemovesLinkToDeletedElementWhenKeepingUnpublished(): void
    {
        RuntimeCache::clear();

        $text = 'Link to a document <a href="/some/path" pimcore_id="99999999" pimcore_type="document">Link text</a>';

        $this->assertEquals('Link to a document Link text', Text::wysiwygTextKeepingLinks($text));
    }

    public function testDocumentEditableEditmodeKeepsLinkToUnpublishedDocument(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = $this->createDocument('unpublished-editable', $this->testingDocument->getParentId(), false);
        $text = sprintf(
            '<a href="/some/path" pimcore_id="%s" pimcore_type="document">Link text</a>',
            $unpublishedDocument->getId()
        );

        $editable = new Document\Editable\Wysiwyg();
        $editable->setDocument($this->testingDocument);
        $editable->setDataFromResource($text);

        $this->assertEquals($text, $editable->getDataEditmode());
    }

    private function createDocument(string $key, int $parentId, bool $published = true): Document\Page
    {
        $document = new Document\Page();
        $document->setKey($key);
        $document->setPublished($published);
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
