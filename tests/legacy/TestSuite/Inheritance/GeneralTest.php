<?php
/**
 * Created by IntelliJ IDEA.
 * User: josef.aichhorn@elements.at
 * Date: 11.11.2013
 */


class TestSuite_Inheritance_GeneralTest extends Test_Base
{
    public function setUp()
    {
        $this->inAdminMode = Pimcore::inAdmin();
        Pimcore::setAdminMode();
        Test_Tool::cleanUp();
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->inAdminMode) {
            Pimcore::setAdminMode();
        } else {
            Pimcore::unsetAdminMode();
        }
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
        $this->printTestName();
        // According to the bootstrap file en and de are valid website languages

        $one = new Object_Inheritance();
        $one->setKey("one");
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput("parenttext");
        $one->save();
        $id1 = $one->getId();

        $two = new Object_Inheritance();
        $two->setKey("two");
        $two->setParentId($one->getId());
        $two->setPublished(1);
        $two->setNormalInput("childtext");
        $two->save();

        $id2 = $two->getId();
        $one = Object_Abstract::getById($id1);
        $two = Object_Abstract::getById($id2);

        $this->assertEquals("parenttext", $one->getNormalInput());
        // not inherited
        $this->assertEquals("childtext", $two->getNormalInput());


        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = Object_Abstract::getById($id2);
        $this->assertEquals("parenttext", $two->getNormalInput());

        $list = new Object_Inheritance_List();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale("de");
        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), "Expected two list items");

        // set it back
        $two->setNormalInput("childtext");
        $two->save();
        $two = Object_Abstract::getById($id2);

        $list = new Object_Inheritance_List();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale("de");
        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), "Expected one list item for de");

        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = Object_Abstract::getById($id2);
        $this->assertEquals("parenttext", $two->getNormalInput());

        // disable inheritance
        $getInheritedValues = Object_Abstract::getGetInheritedValues();
        Object_Abstract::setGetInheritedValues(false);

        $two = Object_Abstract::getById($id2);
        $this->assertEquals(null, $two->getNormalInput());

        // enable inheritance
        Object_Abstract::setGetInheritedValues($getInheritedValues);
        $two = Object_Abstract::getById($id2);
        $this->assertEquals("parenttext", $two->getNormalInput());

        // now move it out

        $two->setParentId(1);
        $two->save();

        // value must be null now
        $this->assertEquals(null, $two->getNormalInput());

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals("parenttext", $two->getNormalInput());

        // modify parent object
        $one->setNormalInput("parenttext2");
        $one->save();

        $two = Object_Abstract::getById($id2);
        // check that child objects has been updated as well
        $this->assertEquals("parenttext2", $two->getNormalInput());

        // invalid locale
        $list = new Object_Inheritance_List();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale("xx");
        try {
            $listItems = $list->load();
            $this->fail("Excpected exception");
        } catch (Exception $e) {
        }
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
        $this->printTestName();
        // According to the bootstrap file en and de are valid website languages

        $one = new Object_Inheritance();
        $one->setKey("one");
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setNormalInput("parenttext");
        $one->save();
        $id1 = $one->getId();


        $folder = new Object_Folder();
        $folder->setParent($one);
        $folder->setKey('folder');
        $folder->save();

        $two = new Object_Inheritance();
        $two->setKey("two");
        $two->setParentId($folder->getId());
        $two->setPublished(1);

        $two->setNormalInput("childtext");
        $two->save();

        $one->setRelationobjects([$one]);
        $one->save();

        Pimcore::collectGarbage();

        $two = Object_Inheritance::getById($two->getId());

        $relationobjects = $two->getRelationObjects();
        if (sizeof($relationobjects) != 1) {
            $this->fail('inheritance for object relations failed');
        } else {
            if ($relationobjects[0]->getId() != $one->getId()) {
                $this->fail('inheritance for object relations failed (wrong object)');
            }
        }

        $table = 'object_'.$one->getClassId();
        $db = Pimcore_Resource::get();

        $relationobjectsString = $db->fetchOne("select relationobjects from {$table} where oo_id= ? ", $two->getId());

        if ($relationobjectsString != ','.$one->getId().',') {
            $this->fail("comma separated relation ids not written correctly in object_* view");
        }
    }
}
