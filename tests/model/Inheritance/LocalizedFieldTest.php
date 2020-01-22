<?php

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore\Db;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Tool;

class LocalizedFieldTest extends ModelTestCase
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
     * two is created after one, en fields inherited. two gets moved out and moved in again. Then one gets updated.
     */
    public function testInheritance()
    {
        // According to the bootstrap file en and de are valid website languages

        /** @var Inheritance $one */
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setInput('parenttextEN', 'en');
        $one->setInput('parenttextDE', 'de');
        $one->save();

        /** @var Inheritance $two */
        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        $two->setInput('childtextDE', 'de');
        $two->save();

        /** @var Inheritance $three */
        $three = new Inheritance();
        $three->setKey('three');
        $three->setParentId($two->getId());
        $three->setPublished(true);
        $three->save();

        $id1 = $one->getId();
        $id2 = $two->getId();
        $id3 = $three->getId();

        $one = AbstractObject::getById($id1);
        $two = AbstractObject::getById($id2);
        $three = AbstractObject::getById($id3);

        $three->delete();

        $this->assertEquals('parenttextEN', $one->getInput('en'));
        $this->assertEquals('parenttextEN', $two->getInput('en'));
        $this->assertEquals('parenttextEN', $three->getInput('en'));

        $three->delete();

        $this->assertEquals('parenttextDE', $one->getInput('de'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // null it out
        $two->setInput(null, 'de');
        $two->save();

        $two = AbstractObject::getById($id2);
        $this->assertEquals('parenttextDE', $two->getInput('de'));

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('de');

        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items for de');

        // set it back
        $two->setInput('childtextDE', 'de');
        $two->save();
        $two = AbstractObject::getById($id2);

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('en');

        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items for en');

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('de');

        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for de');

        $getInheritedValues = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(false);

        $two = AbstractObject::getById($id2);
        $this->assertEquals(null, $two->getInput('en'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        AbstractObject::setGetInheritedValues($getInheritedValues);

        // now move it out

        $two->setParentId(1);
        $two->save();

        $this->assertEquals(null, $two->getInput('en'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals('parenttextEN', $two->getInput('en'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // modify parent object
        $one->setInput('parenttextEN2', 'en');
        $one->save();

        $two = AbstractObject::getById($id2);
        $this->assertEquals('parenttextEN2', $two->getInput('en'));

        // now turn inheritance off
        $class = $one->getClass();
        $class->setAllowInherit(false);
        $class->save();

        $one = AbstractObject::getById($id2);
        $two = AbstractObject::getById($id2);

        // save both objects again
        $one->save();
        $two->save();

        $two = AbstractObject::getById($id2);
        $this->assertEquals(null, $two->getInput('en'));

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('en');

        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for en');

        // turn it back on
        $class->setAllowInherit(true);
        $class->save();
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidLocaleList()
    {
        $this->markTestSkipped('TODO: the following test should fail, but no exception is thrown');

        // invalid locale
        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('xx');

        $listItems = $list->load();
    }

    public function testQueryTable()
    {
        // create root -> one -> two -> three

        /** @var Inheritance $one */
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->save();

        /** @var Inheritance $two */
        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);
        $two->save();

        /** @var Inheritance $three */
        $three = new Inheritance();
        $three->setKey('three');
        $three->setParentId($two->getId());
        $three->setPublished(true);
        $three->save();

        $id1 = $one->getId();
        $id2 = $two->getId();
        $id3 = $three->getId();

        $db = Db::get();
        $query = 'SELECT * FROM object_localized_data_inheritance WHERE ooo_id = ' . $two->getId() . ' GROUP BY ooo_id';
        $result = $db->fetchAll($query);
        // pick the language
        $this->assertCount(1, $result);

        $groupByLanguage = $result[0]['language'];
        $validLanguages = Tool::getValidLanguages();
        $this->assertTrue(in_array($groupByLanguage, $validLanguages), 'not in valid languages');
        if (count($validLanguages) < 2) {
            $this->markTestSkipped('need at least two languages');
        }

        $otherLanguage = null;
        foreach ($validLanguages as $language) {
            if ($language != $groupByLanguage) {
                $otherLanguage = $language;
                break;
            }
        }

        $this->assertTrue(strlen($otherLanguage) > 0, 'need alternative language');

        $two->setInput('SOMEINPUT', $groupByLanguage);
        $two->save();
        // check that it is in the query table for the $groupByLanguage
        $result = $db->fetchAll('SELECT * from object_localized_query_inheritance_' . $groupByLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals('SOMEINPUT', $result[0]['input']);

        // and null for the alternative language
        $result = $db->fetchAll('SELECT * from object_localized_query_inheritance_' . $otherLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals(null, $result[0]['input']);

        // now update the parent for the alternative language, use the same value !!!
        $one->setInput('SOMEINPUT', $otherLanguage);
        $one->save();

        // now the alternative input value in the query table should be SOMEINPUT as well!!!
        $result = $db->fetchAll('SELECT * from object_localized_query_inheritance_' . $otherLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals('SOMEINPUT', $result[0]['input']);

        var_dump($result);
    }
}
