<?php

namespace Pimcore\Tests\Model\Document;

use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class DocumentTest
 * @package Pimcore\Tests\Model\Document
 * @group model.document.document
 */
class DocumentTest extends ModelTestCase
{
    public function testSetGetChildren()
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $childDoc = TestHelper::createEmptyDocumentPage('child1-', false);
        $parentDoc->setChildren([$childDoc]);

        $this->assertSame($parentDoc->getChildren()[0], $childDoc);
    }

    public function testCacheChildren()
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $firstChildDoc = TestHelper::createEmptyDocumentPage('child1-', false); //published child
        $firstChildDoc->setParentId($parentDoc->getId());
        $firstChildDoc->save();

        $secondChildDoc = TestHelper::createEmptyDocumentPage('child2-', false, false);  //unpublished child
        $secondChildDoc->setParentId($parentDoc->getId());
        $secondChildDoc->setPublished(false);
        $secondChildDoc->save();

        $this->assertTrue($parentDoc->hasChildren(), "Expected parent doc has children");

        $publishedChildren = $parentDoc->getChildren();
        $this->assertEquals(1, count($publishedChildren), "Expected 1 child");

        $children = $parentDoc->getChildren(true);
        $this->assertEquals(2, count($children), "Expected 2 children");
    }

    public function testCacheSiblings()
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $firstChildDoc = TestHelper::createEmptyDocumentPage('child1-', false); //published child
        $firstChildDoc->setParentId($parentDoc->getId());
        $firstChildDoc->save();

        $secondChildDoc = TestHelper::createEmptyDocumentPage('child2-', false, false);  //unpublished child
        $secondChildDoc->setParentId($parentDoc->getId());
        $secondChildDoc->setPublished(false);
        $secondChildDoc->save();

        $this->assertEquals(0, count($firstChildDoc->getSiblings()), "Expected no sibling");

        $this->assertEquals(1, count($firstChildDoc->getSiblings(true)), "Expected 1 sibling");
    }
}