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

namespace Pimcore\Tests\Model\Document;

use Exception;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Listing;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class DocumentTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.document.document
 */
class DocumentTest extends ModelTestCase
{
    protected ?Page $testPage = null;

    public function testCRUD(): void
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

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createEmptyDocumentPage('', false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(0);
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be null
     */
    public function testParentIsNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createEmptyDocumentPage('', false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(null);
        $savedObject->save();
    }

    /**
     * Verifies that an object with the same parent ID cannot be created.
     */
    public function testParentIdentical(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
        $savedObject = TestHelper::createEmptyDocumentPage();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Parent ID must resolve to an existing element
     *
     * @group notfound
     */
    public function testParentNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID not found.');
        $savedObject = TestHelper::createEmptyDocumentPage('', false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(999999);
        $savedObject->save();
    }

    /**
     * Verifies that asset PHP API version note is saved
     */
    public function testSavingVersionNotes(): void
    {
        $versionNote = ['versionNote' => 'a new version of this document'];
        $this->testPage = TestHelper::createEmptyDocumentPage();
        $this->testPage->save($versionNote);
        $this->assertEquals($this->testPage->getLatestVersion(null, true)->getNote(), $versionNote['versionNote']);
    }

    public function reloadPage(): void
    {
        $this->testPage = Page::getById($this->testPage->getId(), ['force' => true]);
    }

    public function testCacheChildren(): void
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

    public function testCacheSiblings(): void
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $firstChildDoc = TestHelper::createEmptyDocumentPage('child1-', false); //published child
        $firstChildDoc->setParentId($parentDoc->getId());
        $firstChildDoc->save();

        $secondChildDoc = TestHelper::createEmptyDocumentPage('child2-', false, false);  //unpublished child
        $secondChildDoc->setParentId($parentDoc->getId());
        $secondChildDoc->setPublished(false);
        $secondChildDoc->save();

        $this->assertEquals(0, $firstChildDoc->getSiblings()->count(), 'Expected no sibling');

        $this->assertEquals(1, $firstChildDoc->getSiblings(true)->count(), 'Expected 1 sibling');
    }

    /**
     * Verifies that a document can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate(): void
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
        $document = \Pimcore\Model\Document::getById($document->getId(), ['force' => true]);
        $document->save();
        $this->assertGreaterThanOrEqual($currentTime, $document->getModificationDate(), 'Expected auto assigned modification date');
    }

    /**
     * Verifies that a document can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification(): void
    {
        $userId = 101;
        $document = TestHelper::createEmptyDocumentPage();

        //custom user modification
        $document->setUserModification($userId);
        $document->save();
        $this->assertEquals($userId, $document->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $document = \Pimcore\Model\Document::getById($document->getId(), ['force' => true]);
        $document->save();
        $this->assertEquals(0, $document->getUserModification(), 'Expected auto assigned user modification id');
    }

    public function testEmail(): void
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
        $emailDocument = Email::getById($emailDocument->getId(), ['force' => true]);

        $this->assertEquals($subject, $emailDocument->getSubject());
        $this->assertEquals($to, $emailDocument->getTo());
        $this->assertEquals($cc, $emailDocument->getCc());
        $this->assertEquals($bcc, $emailDocument->getBcc());
        $this->assertEquals($from, $emailDocument->getFrom());
        $this->assertEquals($replyTo, $emailDocument->getReplyTo());
    }

    public function testInheritance(): void
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
        $child = Page::getById($sibling->getId(), ['force' => true]);
        // editable should still be null as no main document is set

        $childEditable = $child->getEditable('headline');
        $this->assertNull($childEditable);

        // set main document
        $child->setContentMainDocumentId($this->testPage->getId(), true);
        $child->save();
        $child = Page::getById($child->getId(), ['force' => true]);

        // now the value should get inherited
        $childEditable = $child->getEditable('headline');
        $this->assertEquals('test', $childEditable->getValue());

        // Don't set the main document if the document is already a part of the main document chain
        $testFirstPage = TestHelper::createEmptyDocumentPage();
        $testSecondPage = TestHelper::createEmptyDocumentPage();
        $testFirstPage->setContentMainDocumentId($testSecondPage->getId(), true);
        $testFirstPage->setPublished(true);
        $testFirstPage->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This document is already part of the main document chain, please choose a different one.');
        $testSecondPage->setContentMainDocumentId($testFirstPage->getId(), true);
    }

    public function testLink(): void
    {
        $target = TestHelper::createImageAsset();

        /** @var Link $linkDocument */
        $linkDocument = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Link');
        $linkDocument->setInternalType('asset');
        $linkDocument->setInternal($target->getId());
        $linkDocument->setLinktype('internal');
        $linkDocument->save();

        $linkDocument = Link::getById($linkDocument->getId());
        $newTarget = $linkDocument->getElement();
        $this->assertEquals($target->getId(), $newTarget->getId());
    }

    public function testLinkItself(): void
    {
        /** @var Link $linkDocument */
        $linkDocument = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Link');
        $linkDocument->setInternalType('document');
        $linkDocument->setInternal(1);
        $linkDocument->setLinktype('internal');
        $linkDocument->save();

        $this->assertEquals(1, $linkDocument->getInternal());

        //Set the same internal target id as itself
        codecept_debug('[WARNING] Testing document/link circular reference, if it is not progressing from here, please stop the tests and fix the code');
        $linkDocument->setInternal($linkDocument->getId());
        $linkDocument->save();

        $linkDocument = Link::getById($linkDocument->getId());

        // when trying to set the target id as itself, it silently logs and saves internal ID as NULL
        $this->assertNull($linkDocument->getInternal());
    }

    public function testSetGetChildren(): void
    {
        $parentDoc = TestHelper::createEmptyDocumentPage();

        $childDoc = TestHelper::createEmptyDocumentPage('child1-', false);
        $listing = new Listing();
        $listing->setData([$childDoc]);
        $parentDoc->setChildren($listing);

        $this->assertSame($parentDoc->getChildren()->getDocuments()[0], $childDoc);
    }

    public function testDocumentSerialization(): void
    {
        $document = TestHelper::createEmptyDocumentPage('some-prefix', true, false);

        $input = new Input();
        $input->setName('testinput');
        $input->setDataFromEditmode('foo');

        $document->setEditable($input);

        $session = $this->buildSession();
        ElementService::saveElementToSession($document, $session->getId());
        $loadedDocument = Service::getElementFromSession('document', $document->getId(), $session->getId());

        $this->assertEquals(count($document->getEditables()), count($loadedDocument->getEditables()));
    }
}
