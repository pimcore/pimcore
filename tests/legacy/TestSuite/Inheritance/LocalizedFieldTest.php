<?php
/**
 * Created by IntelliJ IDEA.
 * User: josef.aichhorn@elements.at
 * Date: 11.11.2013
 */


class TestSuite_Inheritance_LocalizedFieldTest extends Test_Base
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
     * two is created after one, en fields inherited. two gets moved out and moved in again. Then one gets updated.
     */
    public function testInheritance()
    {
        $this->printTestName();
        // According to the bootstrap file en and de are valid website languages

        $one = new Object_Inheritance();
        $one->setKey("one");
        $one->setParentId(1);
        $one->setPublished(1);

        $one->setInput("parenttextEN", "en");
        $one->setInput("parenttextDE", "de");
        $one->save();
        $id1 = $one->getId();

        $two = new Object_Inheritance();
        $two->setKey("two");
        $two->setParentId($one->getId());
        $two->setPublished(1);
        $two->setInput("childtextDE", "de");
        $two->save();

        $three = new Object_Inheritance();
        $three->setKey("three");
        $three->setParentId($two->getId());
        $three->setPublished(1);
        $three->save();


        $id2 = $two->getId();
        $id3 = $three->getId();

        $one = Object_Abstract::getById($id1);
        $two = Object_Abstract::getById($id2);
        $three = Object_Abstract::getById($id3);


        $three->delete();

        $this->assertEquals("parenttextEN", $one->getInput("en"));
        $this->assertEquals("parenttextEN", $two->getInput("en"));
        $this->assertEquals("parenttextEN", $three->getInput("en"));

        $three->delete();

        $this->assertEquals("parenttextDE", $one->getInput("de"));
        $this->assertEquals("childtextDE", $two->getInput("de"));

        // null it out
        $two->setInput(null, "de");
        $two->save();
        $two = Object_Abstract::getById($id2);
        $this->assertEquals("parenttextDE", $two->getInput("de"));

        $list = new Object_Inheritance_List();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale("de");
        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), "Expected two list items for de");

        // set it back
        $two->setInput("childtextDE", "de");
        $two->save();
        $two = Object_Abstract::getById($id2);

        $list = new Object_Inheritance_List();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale("en");
        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), "Expected two list items for en");

        $list = new Object_Inheritance_List();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale("de");
        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), "Expected one list item for de");



        $getInheritedValues = Object_Abstract::getGetInheritedValues();
        Object_Abstract::setGetInheritedValues(false);

        $two = Object_Abstract::getById($id2);
        $this->assertEquals(null, $two->getInput("en"));
        $this->assertEquals("childtextDE", $two->getInput("de"));

        Object_Abstract::setGetInheritedValues($getInheritedValues);

        // now move it out

        $two->setParentId(1);
        $two->save();

        $this->assertEquals(null, $two->getInput("en"));
        $this->assertEquals("childtextDE", $two->getInput("de"));

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals("parenttextEN", $two->getInput("en"));
        $this->assertEquals("childtextDE", $two->getInput("de"));

        // modify parent object
        $one->setInput("parenttextEN2", "en");
        $one->save();

        $two = Object_Abstract::getById($id2);
        $this->assertEquals("parenttextEN2", $two->getInput("en"));


        // now turn inheritance off
        $class = $one->getClass();
        $class->setAllowInherit(false);
        $class->save();

        $one = Object_Abstract::getById($id2);
        $two = Object_Abstract::getById($id2);

        // save both objects again
        $one->save();
        $two->save();

        $two = Object_Abstract::getById($id2);
        $this->assertEquals(null, $two->getInput("en"));


        $list = new Object_Inheritance_List();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale("en");
        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), "Expected one list item for en");

        // turn it back on
        $class->setAllowInherit(true);
        $class->save();

        // invalid locale
        $list = new Object_Inheritance_List();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale("xx");
        try {
            $listItems = $list->load();
            $this->fail("Expected exception");
        } catch (Exception $e) {
        }
    }
}
