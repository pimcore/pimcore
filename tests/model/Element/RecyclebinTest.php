<?php

namespace Pimcore\Tests\Model\Element;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\User;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class RecyclebinTest
 *
 * @package Pimcore\Tests\Model\Element
 * @group model.element.recyclebin
 */
class RecyclebinTest extends ModelTestCase
{
    protected $user;

    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createDummyUser();
    }

    protected function createDummyUser()
    {
        if (!$user = User::getByName('test-user')) {
            $user = new User();
            $user->setAdmin(1);
            $user
                ->setName('test-user')
                ->save();
        }

        $this->user = $user;
    }

    /**
     * Verifies that an object can be moved to recycle bin and restored
     *
     */
    public function testSimpleObjectRecycleAndRestore()
    {
        $object = TestHelper::createEmptyObject();
        $objectId = $object->getId();

        //add to recyclebin
        Item::create($object, $this->user);

        $object->delete();

        //recycle asserts
        $recycledItems = new Item\Listing();
        $this->assertFileExists($recycledItems->current()->getStoreageFile());

        $recycledStorage = unserialize(file_get_contents($recycledItems->current()->getStoreageFile()));
        $this->assertEquals($objectId, $recycledStorage->getId(), 'Recycled Object not found.');

        //restore asserts
        $recycledItems->current()->restore();

        $restoredObject = AbstractObject::getById($objectId);
        $this->assertIsObject($restoredObject, 'Restored simple object');
    }

    /**
     * Verifies that object with children can be moved to recyclebin and restored
     *
     */
    public function testRecursiveObjectRecycleAndRestore()
    {
        // create parent object
        $parent = TestHelper::createEmptyObject();
        $parentId = $parent->getId();
        $parentPath = $parent->getFullPath();

        // create child object
        $child = TestHelper::createEmptyObject();
        $child->setParentId($parentId);
        $child->save();
        $childId = $child->getId();

        //add to recyclebin
        Item::create($parent, $this->user);

        $parent->delete();

        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('path = ?', $parentPath);

        $this->assertEquals(2, $recycledItems->current()->getAmount(), 'Expected 2 recycled item');

        //recycle bin item storage file
        $recycledContent = unserialize(file_get_contents($recycledItems->current()->getStoreageFile()));

        $this->assertEquals($parentId, $recycledContent->getId(), 'Expected recycled parent object ID');
        $this->assertCount(1, $recycledContent->getChildren([AbstractObject::OBJECT_TYPE_FOLDER, AbstractObject::OBJECT_TYPE_VARIANT, AbstractObject::OBJECT_TYPE_OBJECT], true), 'Expected recycled child object');

        //restore deleted items (parent + child)
        $recycledItems->current()->restore();

        $restoredParent = AbstractObject::getById($parentId);
        $restoredChild = AbstractObject::getById($childId);

        $this->assertIsObject($restoredParent, 'Expected restored parent object');
        $this->assertIsObject($restoredChild, 'Expected restored child object');
    }

    /**
     * Verifies that an object data is restored properly
     *
     */
    public function testObjectDataRecycleAndRestore()
    {
        // create target object
        $inputText = TestHelper::generateRandomString();

        //create relation object
        $relationObject = TestHelper::createEmptyObject();

        // create source object
        $sourceObject = TestHelper::createEmptyObject();
        $sourceObject->setInput($inputText);
        $sourceObject->setObjects([$relationObject]); //set relation
        $sourceObject->setLobjects([$relationObject]); //set localized relation
        $sourceObject->save();

        $sourceObjectPath = $sourceObject->getFullPath();
        $sourceObjectId = $sourceObject->getId();

        //add to recyclebin
        Item::create($sourceObject, $this->user);
        $sourceObject->delete();

        //restore deleted items (parent + child)
        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('path = ?', $sourceObjectPath);
        $recycledItems->current()->restore();

        //load relation and check if relation loads correctly
        $restoredSourceObject = AbstractObject::getById($sourceObjectId);
        $restoredRelation = $restoredSourceObject->getLobjects();
        $restoredLocalizedRelation = $restoredSourceObject->getLobjects();

        $this->assertEquals($inputText, $restoredSourceObject->getInput(), 'Input data not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredRelation[0]->getId(), 'Simple object relation not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredLocalizedRelation[0]->getId(), 'Localized object relation not restored properly');
    }
}
