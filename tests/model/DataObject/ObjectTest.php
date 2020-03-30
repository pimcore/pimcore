<?php

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class ObjectTest
 *
 * @package Pimcore\Tests\Model\DataObject
 * @group model.dataobject.object
 */
class ObjectTest extends ModelTestCase
{
    /**
     * Verifies that a object with the same parent ID cannot be created.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage ParentID and ID is identical, an element can't be the parent of itself.
     */
    public function testParentIdentical()
    {
        $savedObject = TestHelper::createEmptyObject();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be 0
     *
     * @expectedException \Exception
     * @expectedExceptionMessage ParentID and ID is identical, an element can't be the parent of itself.
     */
    public function testParentIs0()
    {
        $savedObject = TestHelper::createEmptyObject('', false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(0);
        $savedObject->save();
    }

    /**
     * Verifies that children result should be cached based on parameters provided.
     *
     */
    public function testCacheUnpublishedChildren()
    {
        // create parent
        $parent = TestHelper::createEmptyObject();

        // create first child
        $firstChild = TestHelper::createEmptyObject('child-', false, false);
        $firstChild->setParentId($parent->getId());
        $firstChild->save();

        //without unpublished flag
        $child = $parent->getChildren();
        $this->assertEquals(0, count($child), 'Expected no child');

        $hasChild = $parent->hasChildren();
        $this->assertFalse($hasChild, 'hasChild property should be false');

        //with unpublished flag
        $child = $parent->getChildren([], true);
        $this->assertEquals(1, count($child), 'Expected 1 child');

        $hasChild = $parent->hasChildren([], true);
        $this->assertTrue($hasChild, 'hasChild property should be true');
    }

    /**
     * Verifies that siblings result should be cached based on parameters provided.
     *
     */
    public function testCacheUnpublishedSiblings()
    {
        // create parent
        $parent = TestHelper::createEmptyObject();

        // create first child
        $firstChild = TestHelper::createEmptyObject('child-', false);
        $firstChild->setParentId($parent->getId());
        $firstChild->save();

        // create first child
        $secondChild = TestHelper::createEmptyObject('child-', false, false);
        $secondChild->setParentId($parent->getId());
        $secondChild->save();

        //without unpublished flag
        $sibling = $firstChild->getSiblings();
        $this->assertEquals(0, count($sibling), 'Expected no sibling');

        $hasSibling = $firstChild->hasSiblings();
        $this->assertFalse($hasSibling, 'hasSiblings property should be false');

        //with unpublished flag
        $sibling = $firstChild->getSiblings([], true);
        $this->assertEquals(1, count($sibling), 'Expected 1 sibling');

        $hasSibling = $firstChild->hasSiblings([], true);
        $this->assertTrue($hasSibling, 'hasSiblings property should be true');
    }

    /**
     * Verifies that an object can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification()
    {
        $userId = 101;
        $object = TestHelper::createEmptyObject();

        //custom user modification
        $object->setUserModification($userId);
        $object->save();
        $this->assertEquals($userId, $object->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $object = DataObject::getById($object->getId(), true);
        $object->save();
        $this->assertEquals(0, $object->getUserModification(), 'Expected auto assigned user modification id');
    }

    /**
     * Verifies that an object can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate()
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $object = TestHelper::createEmptyObject();

        //custom modification date
        $object->setModificationDate($customDateTime->getTimestamp());
        $object->save();
        $this->assertEquals($customDateTime->getTimestamp(), $object->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $object = DataObject::getById($object->getId(), true);
        $object->save();
        $this->assertGreaterThanOrEqual($currentTime, $object->getModificationDate(), 'Expected auto assigned modification date');
    }
}
