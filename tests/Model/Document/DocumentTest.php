<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class DocumentTest
 *
 * @package Pimcore\Tests\Model\Document
 * @group model.document.document
 */
class DocumentTest extends ModelTestCase
{
    /** @var Page */
    protected $testPage;

    public function testCRUD()
    {
        // create
        $this->testPage = TestHelper::createEmptyDocumentPage();
        $this->assertInstanceOf(Page::class, $this->testPage);

        $this->reloadPage();
        $this->assertInstanceOf(Page::class, $this->testPage);

        $this->testPage->setController("App\Controller\NewsController::listingAction");
        $this->testPage->save();
        $this->reloadPage();

        $this->assertEquals("App\Controller\NewsController::listingAction", $this->testPage->getController());

        // move and rename
        $newParent = Service::createFolderByPath(uniqid());
        $newPath = $newParent->getFullPath() . '/' . $this->testPage->getKey() . '_new';

        $this->testPage->setParentId($newParent->getId());
        $this->testPage->setKey($this->testPage->getKey() . '_new');
        $this->testPage->save();
        $this->reloadPage();

        $byPath = Page::getByPath($newPath);
        $this->assertInstanceOf(Page::class, $byPath);
        $this->assertEquals($this->testPage->getId(), $byPath->getId());

        $this->assertTrue($newParent->hasChildren());

        // delete
        $this->testPage->delete();

        $this->reloadPage();
        $this->assertNull($this->testPage);

        $this->assertFalse($newParent->hasChildren());
    }

    public function reloadPage()
    {
        $this->testPage = Page::getById($this->testPage->getId(), true);
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

        $this->assertTrue($parentDoc->hasChildren(), 'Expected parent doc has children');

        $publishedChildren = $parentDoc->getChildren();
        $this->assertEquals(1, count($publishedChildren), 'Expected 1 child');

        $children = $parentDoc->getChildren(true);
        $this->assertEquals(2, count($children), 'Expected 2 children');
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

        $this->assertEquals(0, count($firstChildDoc->getSiblings()), 'Expected no sibling');

        $this->assertEquals(1, count($firstChildDoc->getSiblings(true)), 'Expected 1 sibling');
    }

    /**
     * Verifies that a document can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate()
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $document = TestHelper::createEmptyDocumentPage();

        //custom modification date
        $document->setModificationDate($customDateTime->getTimestamp());
        $document->save();
        $this->assertEquals($customDateTime->getTimestamp(), $document->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $document = \Pimcore\Model\Document::getById($document->getId(), true);
        $document->save();
        $this->assertGreaterThanOrEqual($currentTime, $document->getModificationDate(), 'Expected auto assigned modification date');
    }

    /**
     * Verifies that a document can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification()
    {
        $userId = 101;
        $document = TestHelper::createEmptyDocumentPage();

        //custom user modification
        $document->setUserModification($userId);
        $document->save();
        $this->assertEquals($userId, $document->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $document = \Pimcore\Model\Document::getById($document->getId(), true);
        $document->save();
        $this->assertEquals(0, $document->getUserModification(), 'Expected auto assigned user modification id');
    }

    public function testEmail()
    {
        /** @var Email $emailDocument */
        $emailDocument = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Email');
        $subject = 'mysubject' . uniqid();
        $to = 'john' . uniqid(). '@doe.com';
        $cc = 'john' . uniqid(). '@doe.com';
        $bcc = 'john' . uniqid(). '@doe.com';
        $from = 'jane' . uniqid() . '@doe.com';
        $replyTo = 'jane' . uniqid() . '@doe.com';

        $emailDocument->setSubject($subject);
        $emailDocument->setTo($to);
        $emailDocument->setCc($cc);
        $emailDocument->setBcc($bcc);
        $emailDocument->setFrom($from);
        $emailDocument->setReplyTo($replyTo);

        $emailDocument->save();
        $emailDocument = Email::getById($emailDocument->getId(), true);

        $this->assertEquals($subject, $emailDocument->getSubject());
        $this->assertEquals($to, $emailDocument->getTo());
        $this->assertEquals($cc, $emailDocument->getCc());
        $this->assertEquals($bcc, $emailDocument->getBcc());
        $this->assertEquals($from, $emailDocument->getFrom());
        $this->assertEquals($replyTo, $emailDocument->getReplyTo());
    }

    public function testInheritance()
    {
        $this->testPage = TestHelper::createEmptyDocumentPage();
        $this->assertInstanceOf(Page::class, $this->testPage);

        $inputEditable = new Input();
        $inputEditable->setName('headline');
        $inputEditable->setDataFromResource('test');
        $this->testPage->setEditable($inputEditable);
        $this->testPage->save();
        $this->reloadPage();

        $inputEditableAfterSave = $this->testPage->getEditable('headline');
        $this->assertEquals('test', $inputEditableAfterSave->getValue());

        // create sibling
        $sibling = TestHelper::createEmptyDocumentPage();
        $this->assertInstanceOf(Page::class, $sibling);
        $siblingEditable = $sibling->getEditable('headline');
        $this->assertNull($siblingEditable);

        // transform to child
        $sibling->setParentId($this->testPage->getId());
        $sibling->save();
        $child = Page::getById($sibling->getId(), true);
        // editable should still be null as no master document is set

        $childEditable = $child->getEditable('headline');
        $this->assertNull($childEditable);

        // set master document
        $child->setContentMasterDocumentId($this->testPage->getId());
        $child->save();
        $child = Page::getById($child->getId(), true);

        // now the value should get inherited
        $childEditable = $child->getEditable('headline');
        $this->assertEquals('test', $childEditable->getValue());
    }

    public function testLink()
    {
        $target = TestHelper::createImageAsset();

        /** @var Link $linkDocument */
        $linkDocument = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Link');
        $linkDocument->setInternalType('asset');
        $linkDocument->setInternal($target->getId());
        $linkDocument->setLinktype('internal');
        $linkDocument->save();

        $linkDocument = Link::getById($linkDocument->getId());
        $newTarget = $linkDocument->getObject();
        $this->assertEquals($target->getId(), $newTarget->getId());
    }

    public function testSetGetChildren()
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $childDoc = TestHelper::createEmptyDocumentPage('child1-', false);
        $parentDoc->setChildren([$childDoc]);

        $this->assertSame($parentDoc->getChildren()[0], $childDoc);
    }

    public function testDocumentSerialization()
    {
        $document = TestHelper::createEmptyDocumentPage('some-prefix', true, false);

        $input = new Input();
        $input->setName('testinput');
        $input->setDataFromEditmode('foo');

        $document->setEditable($input);

        ElementService::saveElementToSession($document);
        $loadedDocument = Service::getElementFromSession('document', $document->getId());

        $this->assertEquals(count($document->getEditables()), count($loadedDocument->getEditables()));
    }
}
