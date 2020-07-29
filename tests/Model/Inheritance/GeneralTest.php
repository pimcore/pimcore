<?php

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class GeneralTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();
        \Pimcore::setAdminMode();
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *        |-two
     *
     * two is created after one. two gets moved out and moved in again. Then one gets updated.
     */
    public function testInheritance()
    {
        // According to the bootstrap file en and de are valid website languages

        /** @var Inheritance $one */
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput('parenttext');
        $one->save();

        /** @var Inheritance $two */
        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(1);
        $two->setNormalInput('childtext');
        $two->save();

        $id1 = $one->getId();
        $id2 = $two->getId();

        $one = AbstractObject::getById($id1);
        $two = AbstractObject::getById($id2);

        $this->assertEquals('parenttext', $one->getNormalInput());
        // not inherited
        $this->assertEquals('childtext', $two->getNormalInput());

        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = AbstractObject::getById($id2);
        $this->assertEquals('parenttext', $two->getNormalInput());

        $list = new Inheritance\Listing();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale('de');
        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items');

        // set it back
        $two->setNormalInput('childtext');
        $two->save();
        $two = AbstractObject::getById($id2);

        $list = new Inheritance\Listing();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale('de');
        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for de');

        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = AbstractObject::getById($id2);
        $this->assertEquals('parenttext', $two->getNormalInput());

        // disable inheritance
        $getInheritedValues = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(false);

        $two = AbstractObject::getById($id2);
        $this->assertEquals(null, $two->getNormalInput());

        // enable inheritance
        AbstractObject::setGetInheritedValues($getInheritedValues);
        $two = AbstractObject::getById($id2);
        $this->assertEquals('parenttext', $two->getNormalInput());

        // now move it out

        $two->setParentId(1);
        $two->save();

        // value must be null now
        $this->assertEquals(null, $two->getNormalInput());

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals('parenttext', $two->getNormalInput());

        // modify parent object
        $one->setNormalInput('parenttext2');
        $one->save();

        $two = AbstractObject::getById($id2);
        // check that child objects has been updated as well
        $this->assertEquals('parenttext2', $two->getNormalInput());

        // TODO the following doesn't work as the catch catches the exception thrown in fail
    }

    /**
     * Tests https://github.com/pimcore/pimcore/pull/6269
     * [Data objects] Override inherited value with same value (break inheritance)
     *
     * @throws \Exception
     */
    public function testEqual()
    {
        // According to the bootstrap file en and de are valid website languages

        $target = new RelationTest();
        $target->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target->setKey('relation-1');
        $target->setPublished(true);
        $target->setSomeAttribute('Some content 1');
        $target->save();

        /** @var Inheritance $one */
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput('parenttext');
        $one->setRelation($target);
        $one->save();

        // create child "two", inherit relation from "one"

        /** @var Inheritance $two */
        $two = new Inheritance();
        $two->setKey('one');
        $two->setParentId($one->getId());
        $two->setPublished(1);

        $two->setNormalInput('parenttext');
        $two->save();

        $inheritanceEnabled = AbstractObject::getGetInheritedValues();

        AbstractObject::setGetInheritedValues(true);
        $fetchedTarget = $two->getRelation();
        $this->assertTrue($fetchedTarget && $fetchedTarget->getId() == $target->getId(), 'expectected inherited target');

        AbstractObject::setGetInheritedValues(false);
        $fetchedTarget = $two->getRelation();
        $this->assertNull($fetchedTarget, 'target should not be inherited');

        // enable inheritance and set the target
        AbstractObject::setGetInheritedValues(true);
        $two = Concrete::getById($two->getId(), true);
        $two->setRelation($target);
        $two->save();

        // disable inheritance and check that the relation has been set on "two"
        AbstractObject::setGetInheritedValues(false);
        $two = Concrete::getById($two->getId(), true);
        $fetchedTarget = $two->getRelation();
        $this->assertTrue($fetchedTarget && $fetchedTarget->getId() == $target->getId(), 'expectected inherited target');

        AbstractObject::setGetInheritedValues($inheritanceEnabled);
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *      | -folder
     *         |-two
     *
     * object relations field should inherit it's values from one to two
     */
    public function testInheritanceWithFolder()
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput('parenttext');
        $one->save();

        $folder = new Folder();
        $folder->setParent($one);
        $folder->setKey('folder');
        $folder->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($folder->getId());
        $two->setPublished(1);

        $two->setNormalInput('childtext');
        $two->save();

        $one->setRelationobjects([$one]);
        $one->save();

        \Pimcore::collectGarbage();

        $two = Inheritance::getById($two->getId());

        $relationobjects = $two->getRelationObjects();

        $this->assertEquals(1, count($relationobjects), 'inheritance for object relations failed');
        $this->assertEquals($one->getId(), $relationobjects[0]->getId(), 'inheritance for object relations failed (wrong object)');

        $db = $this->tester->getContainer()->get('database_connection');
        $table = 'object_' . $one->getClassId();

        $relationobjectsString = $db->fetchColumn('SELECT relationobjects FROM ' . $table . ' WHERE oo_id = ?', [
            $two->getId(),
        ]);

        $this->assertEquals(
            ',' . $one->getId() . ',',
            $relationobjectsString,
            'comma separated relation ids not written correctly in object_* view'
        );
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *      | -object of other class
     *         |-two
     *
     * object relations field should inherit it's values from one to two
     */
    public function testInheritanceWithOtherClassObjectBetween()
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput('parenttext');
        $one->save();

        $objectBetween = new \Pimcore\Model\DataObject\Unittest();
        $objectBetween->setParent($one);
        $objectBetween->setKey('object of other class');
        $objectBetween->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($objectBetween->getId());
        $two->setPublished(1);

        $two->setNormalInput('childtext');
        $two->save();

        $one->setRelationobjects([$one]);
        $one->save();

        \Pimcore::collectGarbage();

        $two = Inheritance::getById($two->getId());

        $this->assertEquals('childtext', $two->getNormalInput(), 'inheritance failed - inherited data although child overwrote it');

        $relationobjects = $two->getRelationObjects();

        $this->assertCount(1, $relationobjects, 'inheritance for object relations failed');
        $this->assertEquals($one->getId(), $relationobjects[0]->getId(), 'inheritance for object relations failed (wrong object)');

        $db = $this->tester->getContainer()->get('database_connection');
        $table = 'object_' . $one->getClassId();

        $relationobjectsString = $db->fetchColumn('SELECT relationobjects FROM ' . $table . ' WHERE oo_id = ?', [
            $two->getId(),
        ]);

        $this->assertEquals(
            ',' . $one->getId() . ',',
            $relationobjectsString,
            'comma separated relation ids not written correctly in object_* view'
        );
    }
}
