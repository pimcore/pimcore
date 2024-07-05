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

namespace Pimcore\Tests\Service\Element;

use Exception;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class VersionTest
 *
 * @package Pimcore\Tests\Service\Element
 *
 */
class VersionTest extends TestCase
{
    protected function mockFileSystemStorageAdapter(): mixed
    {
        return $this->getMockBuilder(FileSystemVersionStorageAdapter::class)
            ->setMethods(null)
            ->getMock();
    }

    protected function mockDbStorageAdapter(): mixed
    {
        return $this->getMockBuilder(DatabaseVersionStorageAdapter::class)
            ->setMethods(null)
            ->setConstructorArgs([Db::get()])
            ->getMock();
    }

    protected function mockDelegateStorageAdapter(int $byteThreshold = 1000): mixed
    {
        return $this->getMockBuilder(Version\Adapter\DelegateVersionStorageAdapter::class)
            ->setMethods(null)
            ->setConstructorArgs([$byteThreshold, $this->mockDbStorageAdapter(), $this->mockFileSystemStorageAdapter()])
            ->getMock();
    }

    protected function setStorageAdapter(VersionStorageAdapterInterface $adapter): void
    {
        $proxy = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
        $proxy->setStorageAdapter($adapter);
    }

    protected function getVersionDataFromDb(int $id, string $cType, int $versionCount): array|bool
    {
        $query = "select v.id, v.binaryFileId, v.binaryFileHash, v.storageType, vd.metaData, vd.binaryData from versions v
        left join
        versionsData vd on
        v.id = vd.id and v.cid = vd.cid and v.ctype = vd.ctype
        where v.cid = $id and v.ctype = '$cType' and v.versionCount = $versionCount";

        $db = Db::get();

        return $db->fetchAssociative($query);
    }

    /**
     * @throws Exception
     */
    public function testDisable(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
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
    public function testCondense(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        /** @var Unittest $savedObject */

        // create target object
        $randomText = TestHelper::generateRandomString(190);

        /** @var Unittest $targetObject */
        $targetObject = TestHelper::createEmptyObject();
        $targetObject->setInput($randomText);
        $targetObject->save();

        // create source object
        /** @var Unittest $sourceObject */
        $sourceObject = TestHelper::createEmptyObject();

        $sourceObject->setMultihref([$targetObject]);
        $sourceObject->save();

        $sourceObjectFromDb = Unittest::getById($sourceObject->getId(), ['force' => true]);

        $targetObjects = $sourceObject->getMultihref();
        $this->assertCount(1, $targetObjects, 'expected one target');

        $targetObject = $targetObjects[0];
        $this->assertEquals($randomText, $targetObject->getInput(), 'random text does not match');

        $latestVersion1 = $this->getNewestVersion($sourceObject->getId());
        $content = stream_get_contents($latestVersion1->getFileStream());
        $this->assertStringNotContainsString($randomText, $content, "random text shouldn't be there");

        $multihref = $sourceObjectFromDb->getMultihref();
        $this->assertCount(1, $multihref, 'expected 1 target element');
    }

    // Save a new object and check if the storagetype is set to fs
    public function testStorageAdapterTypeFS(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $object = TestHelper::createEmptyObject();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('fs', $result['storageType'], 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'metaData must be empty.');
        $this->assertEmpty($result['metaData'], 'metaData must be empty.');
    }

    // Save a new object and check if the storagetype is set to db
    public function testStorageAdapterDB(): void
    {
        $this->setStorageAdapter($this->mockDbStorageAdapter());
        $object = TestHelper::createEmptyObject();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('db', $result['storageType'], 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertNotEmpty($result['metaData'], 'metaData must not be empty.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'binaryData must be empty.');
    }

    // Size of metadata exceeds "byteThreshold". Therefore, the fallback adapter (fs) should be used.
    public function testStorageAdapterDelegate(): void
    {
        $this->setStorageAdapter($this->mockDelegateStorageAdapter(10));
        $randomText = TestHelper::generateRandomString(100);
        $object = TestHelper::createEmptyObject();
        $object->setLastname($randomText);
        $object->save();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('fs', $result['storageType'], 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'metaData must be empty.');
        $this->assertEmpty($result['metaData'], 'metaData must be empty.');
    }

    public function testStorageAdapterFSWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText, true, 'assets/images/image5.jpg');
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $storageType = $result['storageType'];

        $this->assertEquals('fs', $storageType, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $asset->save();

        $result2 = $this->getVersionDataFromDb($cid, 'asset', 2);
        $id2 = $result2['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];
        $storageType2 = $result2['storageType'];

        $this->assertEquals('fs', $storageType2, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEquals($id1, $binaryFileId2, 'binaryFileId must equal id on asset1');
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
    }

    public function testStorageAdapterDBWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->mockDbStorageAdapter());
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText, true, 'assets/images/image5.jpg');
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $binaryData1 = $result['binaryData'];
        $metaData1 = $result['metaData'];
        $storageType = $result['storageType'];

        $this->assertEquals('db', $storageType, 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $this->assertNotEmpty($binaryData1, 'binaryData must not be empty');
        $this->assertNotEmpty($metaData1, 'metaData must not be empty');
        $asset->save();

        $result2 = $this->getVersionDataFromDb($cid, 'asset', 2);
        $id2 = $result2['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];
        $binaryData2 = $result2['binaryData'];
        $metaData2 = $result2['metaData'];
        $storageType2 = $result2['storageType'];

        $this->assertEquals('db', $storageType2, 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertEquals($id1, $binaryFileId2, 'binaryFileId must equal id on asset1');
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
        $this->assertNotEmpty($metaData2, 'metaData must not be empty');
        $this->assertEmpty($binaryData2, 'binaryData must be empty');
    }

    // Size of binary file exceeds "byteThreshold". Therefore, the fallback adapter (fs) should be used.
    public function testStorageAdapterDelegateWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->mockDelegateStorageAdapter(10));
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText, true, 'assets/images/image5.jpg');
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id = $result['id'];
        $binaryFileHash = $result['binaryFileHash'];
        $storageType = $result['storageType'];

        $this->assertEquals('fs', $storageType, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertNotEmpty($binaryFileHash, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id, 'id must not be empty');
    }

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
        $db->executeStatement('DROP TABLE versionsData');
    }

    protected function needsDb(): bool
    {
        return true;
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses(): void
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

    protected function getNewestVersion(int $id): Version
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
