<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Service\Element;

use Exception;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Model\Version\CoauthorContextInterface;
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
    private ?array $originalObjectsConfig = null;

    protected function mockFileSystemStorageAdapter(): mixed
    {
        return $this->getMockBuilder(FileSystemVersionStorageAdapter::class)
            ->onlyMethods([])
            ->getMock();
    }

    protected function mockDbStorageAdapter(): mixed
    {
        return $this->getMockBuilder(DatabaseVersionStorageAdapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([Db::get()])
            ->getMock();
    }

    protected function mockDelegateStorageAdapter(int $byteThreshold = 1000): mixed
    {
        return $this->getMockBuilder(Version\Adapter\DelegateVersionStorageAdapter::class)
            ->onlyMethods([])
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

    public function testCalculatedValueSnapshotIsOptIn(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'valueAtSaveTime');
        $object->save();
        $version = $this->getNewestVersion($object->getId());

        /** @var Unittest $objectFromVersion */
        $objectFromVersion = $version->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertNull($objectFromVersion->getCalculatedValueSnapshot(), 'no snapshot must be captured unless pimcore.objects.versions.calculated_value_snapshot is enabled');
        $this->assertSame('currentValue', $objectFromVersion->getCalculatedValue(), 'without the option the value must be computed live, as before');
    }

    /**
     * Test for https://github.com/pimcore/pimcore/issues/18533
     */
    public function testCalculatedValueSnapshot(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'snapshotValueOne');
        $object->setFirstname('John');
        $object->save();
        $firstVersion = $this->getNewestVersion($object->getId());

        $content = stream_get_contents($firstVersion->getFileStream());
        $this->assertStringContainsString('snapshotValueOne', $content, 'snapshot value must be part of the serialized version data');

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'snapshotValueTwo');
        $object->setFirstname('Jane');
        $object->save();
        $secondVersion = $this->getNewestVersion($object->getId());

        /** @var Unittest $objectFromFirstVersion */
        $objectFromFirstVersion = $firstVersion->loadData();
        // loadData() collects garbage, so the "current" calculator input has to be restored
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'snapshotValueTwo');

        $this->assertSame('snapshotValueOne', $objectFromFirstVersion->getCalculatedValue(), 'class calculator must return the value as of the snapshot');
        $this->assertSame('John some calc', $objectFromFirstVersion->getCalculatedValueExpression(), 'expression must return the value as of the snapshot');
        $this->assertSame('snapshotValueOne', $objectFromFirstVersion->getValueForFieldName('calculatedValue'), 'version preview accessor must return the snapshot value');

        /** @var Unittest $objectFromSecondVersion */
        $objectFromSecondVersion = $secondVersion->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'snapshotValueTwo');

        $this->assertSame('snapshotValueTwo', $objectFromSecondVersion->getCalculatedValue());
        $this->assertSame('Jane some calc', $objectFromSecondVersion->getCalculatedValueExpression());

        // versions created before snapshots were introduced have to fall back to live computation
        $objectFromFirstVersion->setCalculatedValueSnapshot(null);
        $this->assertSame('snapshotValueTwo', $objectFromFirstVersion->getCalculatedValue(), 'without a snapshot the value must be computed live');
    }

    public function testCalculatedValueSnapshotLocalized(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'localizedSnapshotValue');
        $object->save();
        $version = $this->getNewestVersion($object->getId());

        /** @var Unittest $objectFromVersion */
        $objectFromVersion = $version->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'localizedCurrentValue');

        $this->assertSame('localizedCurrentValue', $object->getLcalculatedValue('en'), 'live object must compute the localized value freshly');
        $this->assertSame(
            'localizedSnapshotValue',
            $objectFromVersion->getLcalculatedValue('en'),
            'localized calculated field getter must return the value as of the snapshot'
        );
        $this->assertSame(
            'localizedSnapshotValue',
            $objectFromVersion->getLocalizedFields()->getLocalizedValue('lcalculatedValue', 'en'),
            'localized value lookup used by the version preview must return the snapshot value'
        );
    }

    public function testCalculatedValueEditModeStaysFresh(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'editModeSnapshotValue');
        $object->save();
        $version = $this->getNewestVersion($object->getId());

        /** @var Unittest $objectFromVersion */
        $objectFromVersion = $version->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'editModeCurrentValue');

        $this->assertSame('editModeSnapshotValue', $objectFromVersion->getCalculatedValue(), 'getter must return the snapshot value');

        $context = new CalculatedValue('calculatedValue');
        $context->setContextualData('object', null, null, null);
        $this->assertSame(
            'editModeCurrentValue',
            DataObjectService::getCalculatedFieldValueForEditMode($objectFromVersion, [], $context),
            'edit mode must always compute the value freshly, even on an object loaded from a version'
        );
    }

    public function testCalculatedValueSnapshotClearedOnSave(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'valueBeforeRestore');
        $object->save();
        $version = $this->getNewestVersion($object->getId());

        /** @var Unittest $restoredObject */
        $restoredObject = $version->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'valueAfterRestore');
        $restoredObject->save();

        $this->assertSame('valueAfterRestore', $restoredObject->getCalculatedValue(), 'the snapshot must be discarded when the object is saved again');

        /** @var Unittest $objectFromNewVersion */
        $objectFromNewVersion = $this->getNewestVersion($object->getId())->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertSame('valueAfterRestore', $objectFromNewVersion->getCalculatedValue(), 'the version created on restore must capture freshly computed values');
    }

    public function testCalculatedValueSnapshotSkippedForAutoSave(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'autoSaveValue');
        $autoSaveVersion = $object->saveVersion(true, true, null, true);

        $this->assertNotNull($autoSaveVersion, 'expected an auto-save version to be created');
        $this->assertTrue($autoSaveVersion->isAutoSave(), 'expected the created version to be an auto-save version');

        /** @var Unittest $objectFromVersion */
        $objectFromVersion = $autoSaveVersion->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertNull($objectFromVersion->getCalculatedValueSnapshot(), 'auto-save versions must not capture calculated values');
        $this->assertSame('currentValue', $objectFromVersion->getCalculatedValue(), 'auto-save versions must fall back to live computation');
    }

    public function testCalculatedValueSnapshotPreservedWhenExistingVersionIsSavedAgain(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'historicValue');
        $object->save();
        $version = $this->getNewestVersion($object->getId());

        // e.g. SanityCheckHandler and the version note endpoints write an existing version again
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');
        $version->setNote('written again');
        $version->save();

        /** @var Unittest $objectFromVersion */
        $objectFromVersion = Version::getById($version->getId())->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertSame('historicValue', $objectFromVersion->getCalculatedValue(), 'writing an existing version again must keep its stored values');
    }

    public function testCalculatedValueSnapshotNotCarriedIntoAutoSaveOfRestoredObject(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'historicValue');
        $object->save();

        /** @var Unittest $restoredObject */
        $restoredObject = $this->getNewestVersion($object->getId())->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');
        $this->assertNotNull($restoredObject->getCalculatedValueSnapshot());

        // auto-saving a loaded draft calls saveVersion() directly, update() never runs
        $autoSaveVersion = $restoredObject->saveVersion(true, true, null, true);
        $this->assertNotNull($autoSaveVersion);

        /** @var Unittest $objectFromAutoSave */
        $objectFromAutoSave = $autoSaveVersion->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertNull($objectFromAutoSave->getCalculatedValueSnapshot(), 'an auto-save version must not inherit the snapshot of the restored object');
        $this->assertSame('currentValue', $objectFromAutoSave->getCalculatedValue());
    }

    public function testCalculatedValueSnapshotNotCarriedIntoNewVersionWhenOptionIsDisabled(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'historicValue');
        $object->save();

        /** @var Unittest $restoredObject */
        $restoredObject = $this->getNewestVersion($object->getId())->loadData();
        $this->assertNotNull($restoredObject->getCalculatedValueSnapshot());

        Config::setSystemConfiguration($this->originalObjectsConfig, 'objects');
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');
        $draftVersion = $restoredObject->saveVersion();
        $this->assertNotNull($draftVersion);

        /** @var Unittest $objectFromDraft */
        $objectFromDraft = $draftVersion->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertNull($objectFromDraft->getCalculatedValueSnapshot(), 'with the option disabled a new version must not carry an old snapshot');
        $this->assertSame('currentValue', $objectFromDraft->getCalculatedValue());
    }

    public function testCalculatedValueEditModeComputesDependentFieldsFreshly(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'historicValue');
        $object->save();

        /** @var Unittest $restoredObject */
        $restoredObject = $this->getNewestVersion($object->getId())->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertSame('historicValue dependent', $restoredObject->getCalculatedValueDependent(), 'the getter returns the snapshot of the dependent field');

        $context = new CalculatedValue('calculatedValueDependent');
        $context->setContextualData('object', null, null, null);
        $this->assertSame(
            'currentValue dependent',
            DataObjectService::getCalculatedFieldValueForEditMode($restoredObject, [], $context),
            'in edit mode an expression reading another calculated field must see its live value, not the snapshot'
        );
        $this->assertSame('historicValue dependent', $restoredObject->getCalculatedValueDependent(), 'the snapshot must be restored after the edit mode evaluation');
    }

    public function testCalculatedValueSnapshotNotUsedForLocalizedFieldOfFieldcollectionItem(): void
    {
        $this->enableCalculatedValueSnapshot();
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        /** @var Unittest $object */
        $object = TestHelper::createEmptyObject();
        $item = new Fieldcollection\Data\Unittestfieldcollection();
        $object->setFieldcollection(new Fieldcollection([$item], 'fieldcollection'));

        RuntimeCache::set('modeltest.testCalculatedValue.value', 'historicValue');
        $object->save();

        /** @var Unittest $restoredObject */
        $restoredObject = $this->getNewestVersion($object->getId())->loadData();
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'currentValue');

        $this->assertSame('historicValue', $restoredObject->getLcalculatedValue('en'), 'the localized field of the object itself returns the snapshot');

        /** @var Fieldcollection\Data\Unittestfieldcollection $restoredItem */
        $restoredItem = $restoredObject->getFieldcollection()->get(0);
        $this->assertSame(
            'currentValue',
            $restoredItem->getLcalculatedValue('en'),
            'the same-named localized field of the field collection item must not return the snapshot of the object'
        );
        $this->assertSame(
            'currentValue',
            $restoredItem->getLocalizedfields()->getLocalizedValue('lcalculatedValue', 'en'),
            'the localized value lookup of the item must not return the snapshot of the object either'
        );
    }

    /**
     * getLatestVersion() must only report versions that are newer than the element itself, and the
     * returned version must be hydrated exactly like a directly loaded one.
     */
    public function testGetLatestVersion(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $object = TestHelper::createEmptyObject();

        // the version written by save() represents the published state, so it is only reported
        // when $includingPublished is set
        $this->assertNull($object->getLatestVersion(), 'expected no version newer than the object itself');

        $publishedVersion = $object->getLatestVersion(null, true);
        $this->assertNotNull($publishedVersion, 'expected the published version to be returned');
        $this->assertSame($object->getId(), $publishedVersion->getCid(), 'version belongs to another element');
        $this->assertSame('object', $publishedVersion->getCtype(), 'unexpected element type');
        $this->assertSame($object->getVersionCount(), $publishedVersion->getVersionCount(), 'unexpected version count');
        $this->assertFalse($publishedVersion->isAutoSave(), 'the published version must not be an auto-save one');

        $directlyLoadedVersion = Version::getById((int) $publishedVersion->getId());
        $this->assertNotNull($directlyLoadedVersion, 'the returned version must exist in the database');
        $this->assertSame($directlyLoadedVersion->getDate(), $publishedVersion->getDate(), 'date not hydrated');
        $this->assertSame($directlyLoadedVersion->getVersionCount(), $publishedVersion->getVersionCount(), 'versionCount not hydrated');
        $this->assertSame($directlyLoadedVersion->isAutoSave(), $publishedVersion->isAutoSave(), 'autoSave not hydrated');
        $this->assertSame($directlyLoadedVersion->getSerialized(), $publishedVersion->getSerialized(), 'serialized not hydrated');

        // a version newer than the element wins, regardless of $includingPublished
        $newerVersion = $object->saveVersion();
        $this->assertNotNull($newerVersion, 'expected a new version to be saved');
        $latestVersion = $object->getLatestVersion();
        $this->assertNotNull($latestVersion, 'expected the newly saved version to be returned');
        $this->assertSame($newerVersion->getId(), $latestVersion->getId(), 'expected the newest version');
        $this->assertGreaterThan($object->getVersionCount(), $latestVersion->getVersionCount(), 'expected a newer version count');
    }

    /**
     * Auto-save versions are private to their author: getLatestVersion() must return the caller's
     * own auto-save version, and must never leak somebody else's.
     */
    public function testGetLatestVersionOnlyReturnsOwnAutoSaveVersion(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());

        $author = $this->createUser('test-version-author');
        $otherUser = $this->createUser('test-version-other-user');

        $object = TestHelper::createEmptyObject();
        $object->setUserModification($author->getId());
        $autoSaveVersion = $object->saveVersion(true, true, null, true);
        $this->assertNotNull($autoSaveVersion, 'expected an auto-save version to be saved');
        $this->assertTrue($autoSaveVersion->isAutoSave(), 'expected an auto-save version');

        $latestVersion = $object->getLatestVersion($author->getId());
        $this->assertNotNull($latestVersion, 'the author must see their own auto-save version');
        $this->assertSame($autoSaveVersion->getId(), $latestVersion->getId(), 'expected the auto-save version');
        $this->assertSame($author->getId(), $latestVersion->getUserId(), 'unexpected auto-save author');
        $this->assertTrue($latestVersion->isAutoSave(), 'autoSave not hydrated');

        $this->assertNull(
            $object->getLatestVersion($otherUser->getId()),
            'an auto-save version must not be visible to another user'
        );
    }

    protected function createUser(string $name): User
    {
        if (!$user = User::getByName($name)) {
            $user = new User();
            $user->setName($name)->save();
        }

        return $user;
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

    /**
     * Regression test for https://github.com/pimcore/pimcore/issues/18522
     *
     * fetchOne() yields false when no versionsData row matches. That false has to be
     * normalized to null, otherwise the ?string return type of loadMetaData() and the
     * string argument type of getStream() raise a TypeError under strict_types.
     */
    public function testDbStorageAdapterReturnsNullWhenNoDataRowExists(): void
    {
        $adapter = new DatabaseVersionStorageAdapter(Db::get());

        // a version that never had a corresponding versionsData row
        $version = new Version();
        $version->setId(999999999);
        $version->setCid(999999999);
        $version->setCtype('object');

        $this->assertNull($adapter->loadMetaData($version), 'loadMetaData must return null when no data row exists.');
        $this->assertNull($adapter->getFileStream($version), 'getFileStream must return null when no data row exists.');
        $this->assertNull($adapter->loadBinaryData($version), 'loadBinaryData must return null when no data row exists.');
        $this->assertNull($adapter->getBinaryFileStream($version), 'getBinaryFileStream must return null when no data row exists.');
    }

    /**
     * Regression test for https://github.com/pimcore/pimcore/issues/18522
     *
     * Same missing-row path as above, but reached the way it happens in practice: the
     * versions row outlives its versionsData row.
     */
    public function testDbStorageAdapterReturnsNullAfterDataRowRemoved(): void
    {
        $this->setStorageAdapter($this->mockDbStorageAdapter());
        $object = TestHelper::createEmptyObject();
        $version = $this->getNewestVersion($object->getId());

        $adapter = new DatabaseVersionStorageAdapter(Db::get());
        $this->assertNotNull($adapter->loadMetaData($version), 'metaData must be readable before the data row is removed.');

        Db::get()->delete(DatabaseVersionStorageAdapter::versionsTableName, [
            'id' => $version->getId(),
            'cid' => $version->getCid(),
            'ctype' => $version->getCtype(),
        ]);

        $this->assertNull($adapter->loadMetaData($version), 'loadMetaData must return null once the data row is gone.');
        $this->assertNull($adapter->getFileStream($version), 'getFileStream must return null once the data row is gone.');
        $this->assertNull($adapter->loadBinaryData($version), 'loadBinaryData must return null once the data row is gone.');
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

    // An active coauthor context must stamp a brand-new version with its type/coauthor values.
    public function testCoauthorContextStampsNewVersionOnSave(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $context = Pimcore::getContainer()->get(CoauthorContextInterface::class);

        try {
            $context->set('agent', 'product-data-agent');

            $object = TestHelper::createEmptyObject();
            $version = $this->getNewestVersion($object->getId());

            $reloadedVersion = Version::getById($version->getId());

            $this->assertSame('agent', $reloadedVersion->getCoauthorType(), 'coauthorType must be stamped from context');
            $this->assertSame(
                'product-data-agent',
                $reloadedVersion->getCoauthor(),
                'coauthor must be stamped from context'
            );
        } finally {
            $context->clear();
        }
    }

    // An explicitly set coauthorType/coauthor on the version must win over an active context.
    public function testExplicitCoauthorSetOnVersionWinsOverContext(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $context = Pimcore::getContainer()->get(CoauthorContextInterface::class);

        try {
            $context->set('agent', 'product-data-agent');

            $object = TestHelper::createEmptyObject();

            $version = new Version();
            $version->setCid($object->getId());
            $version->setCtype('object');
            $version->setDate(time());
            $version->setUserId(1);
            $version->setData($object);
            $version->setVersionCount($object->getVersionCount() + 1);
            $version->setCoauthorType('human');
            $version->setCoauthor('jane.doe');
            $version->save();

            $reloadedVersion = Version::getById($version->getId());

            $this->assertSame('human', $reloadedVersion->getCoauthorType(), 'explicit coauthorType must win');
            $this->assertSame('jane.doe', $reloadedVersion->getCoauthor(), 'explicit coauthor must win');
        } finally {
            $context->clear();
        }
    }

    // Re-saving an already persisted version must not stamp coauthor fields, even with an active context.
    public function testResavingExistingVersionDoesNotStampCoauthor(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $context = Pimcore::getContainer()->get(CoauthorContextInterface::class);

        try {
            // save without an active context first: no-context default is null coauthor fields
            $object = TestHelper::createEmptyObject();
            $version = $this->getNewestVersion($object->getId());

            $this->assertNull($version->getCoauthorType(), 'coauthorType must be null without an active context');
            $this->assertNull($version->getCoauthor(), 'coauthor must be null without an active context');

            // activate the context only after the version already exists, then re-save it
            $context->set('agent', 'product-data-agent');

            $loadedVersion = Version::getById($version->getId());
            $loadedVersion->loadData();
            $loadedVersion->setNote('updated note');
            $loadedVersion->save();

            $reloadedVersion = Version::getById($version->getId());

            $this->assertNull($reloadedVersion->getCoauthorType(), 'existing version must not be stamped on re-save');
            $this->assertNull($reloadedVersion->getCoauthor(), 'existing version must not be stamped on re-save');
            $this->assertSame('updated note', $reloadedVersion->getNote(), 'note update must still be persisted');
        } finally {
            $context->clear();
        }
    }

    // Stamping must be a no-op when versioning is disabled: no mutation of the coauthor fields.
    public function testDisabledVersioningDoesNotStampCoauthor(): void
    {
        $this->setStorageAdapter($this->mockFileSystemStorageAdapter());
        $context = Pimcore::getContainer()->get(CoauthorContextInterface::class);

        try {
            $context->set('agent', 'product-data-agent');

            $object = TestHelper::createEmptyObject();

            $version = new Version();
            $version->setCid($object->getId());
            $version->setCtype('object');
            $version->setDate(time());
            $version->setUserId(1);
            $version->setData($object);
            $version->setVersionCount($object->getVersionCount() + 1);

            Version::disable();

            try {
                $version->save();
            } finally {
                Version::enable();
            }

            $this->assertNull($version->getCoauthorType(), 'coauthorType must not be stamped while versioning is disabled');
            $this->assertNull($version->getCoauthor(), 'coauthor must not be stamped while versioning is disabled');
        } finally {
            $context->clear();
        }
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

        if ($this->originalObjectsConfig !== null) {
            Config::setSystemConfiguration($this->originalObjectsConfig, 'objects');
            $this->originalObjectsConfig = null;
        }

        $db = Db::get();
        $db->executeStatement('DROP TABLE versionsData');
    }

    private function enableCalculatedValueSnapshot(): void
    {
        $this->originalObjectsConfig ??= Config::getSystemConfiguration('objects') ?? [];

        $config = $this->originalObjectsConfig;
        $config['versions']['calculated_value_snapshot'] = true;
        Config::setSystemConfiguration($config, 'objects');
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
                                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci");
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
