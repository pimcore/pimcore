<?php

namespace Pimcore\Tests\Service\Element;

use Pimcore\Db;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class VersionTest
 *
 * @package Pimcore\Tests\Service\Element
 *
 */
class VersionTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testDisable()
    {
        $savedObject = TestHelper::createEmptyObject();
        $objectId = $savedObject->getId();

        $query = 'select count(*) from versions where cid = ' . $objectId . " and ctype='object'";
        $db = Db::get();

        $initialCount = $db->fetchOne($query);
        $this->assertEquals(1, $initialCount, 'initial count must be 1');

        $savedObject->save();
        $countAfterSave = $db->fetchOne($query);
        $this->assertEquals(2, $countAfterSave, 'expected a new version');

        // disable versioning, version count should remain the same
        Version::disable();
        $savedObject->save();
        $countAfterSave = $db->fetchOne($query);
        $this->assertEquals(2, $countAfterSave, "seems that Version::disable doesn't work");

        // enable versioning again
        Version::enable();
        $savedObject->save();
        $countAfterSave = $db->fetchOne($query);
        $this->assertEquals(3, $countAfterSave, "seems that Version::enable doesn't work");
    }

    /**
     * Test for https://github.com/pimcore/pimcore/issues/4667
     */
    public function testCondense()
    {
        /** @var Unittest $savedObject */

        // create target object
        $randomText = TestHelper::generateRandomString(10000);

        /** @var Unittest $targetObject */
        $targetObject = TestHelper::createEmptyObject();
        $targetObject->setInput($randomText);
        $targetObject->save();

        // create source object
        /** @var Unittest $sourceObject */
        $sourceObject = TestHelper::createEmptyObject();

        $sourceObject->setMultihref([$targetObject]);
        $sourceObject->save();

        $sourceObjectFromDb = Unittest::getById($sourceObject->getId(), true);

        $targetObjects = $sourceObject->getMultihref();
        $this->assertEquals(1, count($targetObjects), 'expected one target');

        $targetObject = $targetObjects[0];
        $this->assertEquals($randomText, $targetObject->getInput(), 'random text does not match');

        $latestVersion1 = $this->getNewestVersion($sourceObject->getId());
        $content = file_get_contents($latestVersion1->getFilePath());
        $this->assertTrue(strpos($content, $randomText) === false, "random text shouldn't be there");

        $multihref = $sourceObjectFromDb->getMultihref();
        $this->assertEquals(1, count($multihref), 'expected 1 target element');
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    /**
     * @inheritdoc
     */
    protected function needsDb()
    {
        return true;
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses()
    {
    }

    /**
     * @param int $id
     *
     * @return Version
     */
    protected function getNewestVersion($id)
    {
        $list = new Version\Listing();
        $list->setCondition("ctype = 'object' and cid = " . $id);
        $list->setLimit(1);
        $list->setOrderKey('id');
        $list->setOrder('DESC');
        $list = $list->load();
        $version = $list[0];

        return $version;
    }
}
