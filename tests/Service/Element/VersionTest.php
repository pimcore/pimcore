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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Service\Element;

use Pimcore\Db;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
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
        $content = stream_get_contents($latestVersion1->getFileStream());
        $this->assertTrue(strpos($content, $randomText) === false, "random text shouldn't be there");

        $multihref = $sourceObjectFromDb->getMultihref();
        $this->assertEquals(1, count($multihref), 'expected 1 target element');
    }

    /*
     * Save a new object and check if the storagetype is set to fs
     */
    public function testStorageAdapterTypeFS()
    {
        $object = TestHelper::createEmptyObject();

        $query = 'select storageType, binaryFileId from versions where cid = ' . $object->getId() . " and ctype='object'";
        $db = Db::get();
        $result = $db->fetchAssociative($query);

        $this->assertEquals("fs", $result['storageType'], 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
    }

    protected function setStorageAdapter(string $class) {
        $handler = $this->getMockBuilder($class)
            ->setMethods(null)
            ->setConstructorArgs([Db::get()])
            ->getMock();

        \Pimcore::getContainer()->set(VersionStorageAdapterInterface::class, $handler);
    }

    /*
     * Save a new object and check if the storagetype is set to db
     */
    public function testStorageAdapterDB()
    {
        $this->setStorageAdapter(Version\Adapter\DatabaseVersionStorageAdapter::class);
        $object = TestHelper::createEmptyObject();

        $query = "select v.id, v.storageType, vd.metaData, vd.binaryData from versions v inner join
                    versionsData vd on
                    v.id = vd.id and v.cid = vd.cid and v.ctype = vd.ctype
                    where v.cid = " . $object->getId() . " and v.ctype = 'object'";

        $db = Db::get();
        $result = $db->fetchAssociative($query);

        $this->assertEquals("db", $result['storageType'], 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertNotEmpty($result['metaData'], 'metaData must not be empty.');
        $this->assertEmpty($result['binaryData'], 'metaData must not be empty.');
    }

    /*
     * Create asset with image. After that save the same asset again.
     * Since we do not store the same file twice, binaryFileId must be set on the second version.
     */
    public function testStorageAdapterFSWithBinaryFile()
    {
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset("test_binary_file_id", $randomText, true, 'assets/images/image5.jpg');
        $cid = $asset->getId();

        $query = "select id, binaryFileHash, binaryFileId from versions where cid = $cid and ctype='asset'";
        $db = Db::get();
        $result = $db->fetchAssociative($query);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $asset->save();

        $query = "select id, binaryFileHash, binaryFileId from versions where cid = $cid and ctype='asset' and versionCount = 2";
        $result2 = $db->fetchAssociative($query);
        $id2 = $result['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];

        $this->assertEquals($id1, $binaryFileId2, "binaryFileId must equal id on asset1");
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
    }

    /*
    * Create asset with image. After that save the same asset again.
    * Since we do not store the same file twice, binaryFileId must be set on the second version.
    */
    public function testStorageAdapterDBWithBinaryFile()
    {
        $this->setStorageAdapter(Version\Adapter\DatabaseVersionStorageAdapter::class);
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset("test_binary_file_id", $randomText, true, 'assets/images/image5.jpg');
        $cid = $asset->getId();

        $query = "select v.id, v.storageType, v.binaryFileId, v.binaryFileHash, vd.metaData, vd.binaryData from versions v inner join
                    versionsData vd on
                    v.id = vd.id and v.cid = vd.cid and v.ctype = vd.ctype
                    where v.cid = $cid and v.ctype = 'asset'";

        $db = Db::get();
        $result = $db->fetchAssociative($query);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $binaryData1 = $result['binaryData'];
        $metaData1 = $result['metaData'];

        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $this->assertNotEmpty($binaryData1, 'binaryData must not be empty');
        $this->assertNotEmpty($metaData1, 'metaData must not be empty');
        $asset->save();

        $query = "select v.id, v.storageType, v.binaryFileId, v.binaryFileHash, vd.metaData, vd.binaryData from versions v inner join
                    versionsData vd on
                    v.id = vd.id and v.cid = vd.cid and v.ctype = vd.ctype
                    where v.cid = $cid and v.ctype = 'asset' and versionCount = 2";

        $result2 = $db->fetchAssociative($query);
        $id2 = $result['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];
        $binaryData2 = $result2['binaryData'];
        $metaData2 = $result2['metaData'];

        $this->assertEquals($id1, $binaryFileId2, "binaryFileId must equal id on asset1");
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
        $this->assertNotEmpty($metaData2, 'metaData must not be empty');
        $this->assertEmpty($binaryData2, 'binaryData must be empty');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $db = Db::get();
        $db->executeStatement("DROP TABLE versionsData");
    }

    /**
     * {@inheritdoc}
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
        //Create versionsData table. Needed for tests with DatabaseVersionStorageAdapter
        $db = Db::get();
        $db->executeStatement("CREATE TABLE `versionsData` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `cid` int(11) unsigned DEFAULT NULL,
                                  `ctype` enum('document','asset','object') DEFAULT NULL,
                                  `metaData` longblob DEFAULT NULL,
                                  `binaryData` longblob DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                )");
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
