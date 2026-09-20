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

namespace Pimcore\Tests\Model\Element;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;

/**
 * Class RecyclebinTest
 *
 * @package Pimcore\Tests\Model\Element
 *
 * @group model.element.recyclebin
 */
class RecyclebinTest extends ModelTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createDummyUser();
    }

    protected function createDummyUser(): void
    {
        if (!$user = User::getByName('test-user')) {
            $user = new User();
            $user->setAdmin(true);
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
    public function testSimpleObjectRecycleAndRestore(): void
    {
        $object = TestHelper::createEmptyObject();
        $objectId = $object->getId();

        //add to recyclebin
        Item::create($object, $this->user);

        $object->delete();

        $storage = Storage::get('recycle_bin');

        //recycle asserts
        $recycledItems = new Item\Listing();
        $this->assertTrue($storage->fileExists($recycledItems->current()->getStorageFile()));

        $recycledStorage = unserialize($storage->read($recycledItems->current()->getStorageFile()));
        $this->assertEquals($objectId, $recycledStorage->getId(), 'Recycled Object not found.');

        $this->assertEquals($recycledItems->current()->getStorageFile(), $recycledItems->current()->getStoreageFile());    // deprecated method name

        //restore asserts
        $recycledItems->current()->restore();

        $restoredObject = DataObject::getById($objectId);
        $this->assertIsObject($restoredObject, 'Restored simple object');
    }

    /**
     * Verifies that restoring an asset keeps its custom settings, in particular the embedded
     * meta data, which belongs to the restored binary data
     */
    public function testAssetRecycleAndRestoreKeepsEmbeddedMetaData(): void
    {
        $asset = TestHelper::createDocumentAsset();
        $assetId = $asset->getId();
        $asset->setCustomSetting('embeddedMetaData', ['Title' => 'Embedded Meta Data Test']);
        $asset->setCustomSetting('embeddedMetaDataExtracted', true);
        // derived from the binary data as well, must not be invalidated by the subtype when restoring the data
        $asset->setCustomSetting('document_page_count', 3);
        $asset->setCustomSetting(Asset\Document::CUSTOM_SETTING_PDF_SCAN_STATUS, Asset\Enum\PdfScanStatus::SAFE->value);
        // the derived settings are complete, as if the asset update tasks queue had processed the data
        $asset->setProcessingPending(false);
        $asset->save();

        Item::create($asset, $this->user);
        $asset->delete();

        $recycledItems = new Item\Listing();
        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $recycledItems->current()->restore();
        // the restored data must not be processed again, as this could discard the restored derived settings
        $this->assertSame($queueSize, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertTrue($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertEquals(['Title' => 'Embedded Meta Data Test'], $restoredAsset->getCustomSetting('embeddedMetaData'));
        $this->assertSame(3, $restoredAsset->getPageCount());
        $this->assertSame(Asset\Enum\PdfScanStatus::SAFE, $restoredAsset->getScanStatus());
    }

    /**
     * Verifies that restoring an asset keeps its custom settings, even if they were not loaded yet when the
     * asset was added to the recycle bin (custom settings which are too large for the cache are only loaded on access)
     */
    public function testCacheHydratedAssetRecycleAndRestoreKeepsCustomSettings(): void
    {
        $asset = TestHelper::createDocumentAsset();
        $assetId = $asset->getId();
        $asset->setCustomSetting('embeddedMetaData', ['Title' => 'Embedded Meta Data Test']);
        $asset->setCustomSetting('embeddedMetaDataExtracted', true);
        $asset->setCustomSetting('customSettingsTest', 'test');
        $asset->save();

        $asset = TestHelper::getCacheHydratedAsset(Asset::getById($assetId, ['force' => true]));

        Item::create($asset, $this->user);
        $asset->delete();

        $recycledItems = new Item\Listing();
        $recycledItems->current()->restore();

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertTrue($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertEquals(['Title' => 'Embedded Meta Data Test'], $restoredAsset->getCustomSetting('embeddedMetaData'));
        $this->assertSame('test', $restoredAsset->getCustomSetting('customSettingsTest'));
    }

    /**
     * An asset can be added to the recycle bin before the asset update tasks queue processed its data (e.g. extracted
     * the embedded meta data). Restoring it has to process the data again, although the item contains all custom
     * settings of that time.
     */
    public function testRecycleBinItemCreatedBeforeProcessingIsProcessedAgainAfterRestore(): void
    {
        $asset = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $assetId = $asset->getId();
        $this->assertTrue($asset->isProcessingPending());
        Item::create($asset, $this->user);

        // the queue processes the data
        TestHelper::runAssetUpdateTasks($assetId);
        $asset = Asset::getById($assetId, ['force' => true]);
        $this->assertFalse($asset->isProcessingPending());
        $this->assertTrue($asset->getCustomSetting('embeddedMetaDataExtracted'));
        $asset->delete();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        (new Item\Listing())->current()->restore();
        // the processing was still pending when the item was created, so the restored data is processed again ...
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertTrue($restoredAsset->isProcessingPending());
        $this->assertNull($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));

        // ... which generates the derived settings
        TestHelper::runAssetUpdateTasks($assetId);
        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertFalse($restoredAsset->isProcessingPending());
        $this->assertSame('Pimcore Test Suite', $restoredAsset->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * An asset remembers that its custom settings were too large for the cache, even if they are cleared
     * afterwards. A recycle bin item of such an asset with cleared custom settings must not be mistaken for a
     * legacy item without custom settings (see testLegacyRecycleBinItemWithoutCustomSettingsRegeneratesDerivedSettings())
     */
    public function testRecycleBinRestoreKeepsClearedOversizedCustomSettings(): void
    {
        $asset = TestHelper::createDocumentAsset();
        $assetId = $asset->getId();
        $asset = Asset::getById($assetId, ['force' => true]);
        TestHelper::simulateCustomSettingsTooLargeForCache($asset);
        // clearing the custom settings doesn't finish the pending processing of the data (its token is kept when the
        // asset is saved), so it is finished first to get an asset without any custom settings
        $asset->setProcessingPending(false);
        $asset->setCustomSettings([]);
        $asset->save();
        $this->assertSame([], Asset::getById($assetId, ['force' => true])->getCustomSettings());

        Item::create($asset, $this->user);
        $asset->delete();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        (new Item\Listing())->current()->restore();
        // the empty custom settings are restored as they are, without processing the data again
        $this->assertSame($queueSize, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertNull($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
    }

    /**
     * An asset can be added to the recycle bin without saving it after its data was replaced. Such an item contains
     * the new data, but the settings derived from the previous data, so restoring it must not keep them but process
     * the data again
     */
    public function testRecycleBinItemOfReplacedDataIsProcessedAgainAfterRestore(): void
    {
        $asset = TestHelper::createDocumentAsset();
        $assetId = $asset->getId();
        $asset->setCustomSetting('document_page_count', 3);
        $asset->setProcessingPending(false);
        $asset->save();

        // the data is replaced, but the asset is only added to the recycle bin
        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        Item::create($asset, $this->user);
        $asset->delete();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        (new Item\Listing())->current()->restore();
        // the settings derived from the previous data are not restored, the data is processed again instead
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertNull($restoredAsset->getPageCount());
        $this->assertNull($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertTrue($restoredAsset->isProcessingPending());

        TestHelper::runAssetUpdateTasks($assetId);
        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertFalse($restoredAsset->isProcessingPending());
        $this->assertSame('Pimcore Test Suite', $restoredAsset->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * Recycle bin items created before it was tracked whether the processing of the data was still pending when the
     * asset was dumped may have been created while it was pending (before the derived settings existed), so
     * restoring them processes the data again, as it was done in the past
     */
    public function testLegacyRecycleBinItemIsProcessedAgainAfterRestore(): void
    {
        $asset = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $assetId = $asset->getId();
        // a legacy dump doesn't contain the marker of a pending processing
        $asset->setProcessingPending(false);
        $asset->setCustomSetting('customSettingsTest', 'test');
        $asset->save();

        Item::create($asset, $this->user);
        $recycledItem = (new Item\Listing())->current();
        // replace the data of the recycle bin item by a dump in the legacy format
        Storage::get('recycle_bin')->write($recycledItem->getStorageFile(), TestHelper::getLegacyDumpData($asset));
        $asset->delete();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $recycledItem->restore();
        // the data is processed again, as it is unknown whether it was processed when it was dumped
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertTrue($restoredAsset->isProcessingPending());
        // the custom settings of the dump are kept
        $this->assertSame('test', $restoredAsset->getCustomSetting('customSettingsTest'));

        TestHelper::runAssetUpdateTasks($assetId);
        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertFalse($restoredAsset->isProcessingPending());
        $this->assertSame('Pimcore Test Suite', $restoredAsset->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * Recycle bin items created before the custom settings were loaded explicitly before dumping, of an asset that
     * was hydrated from the cache without its custom settings (too large for the cache), don't contain the custom
     * settings at all. Restoring such an item can't restore the derived settings, so they have to be generated
     * again from the restored data.
     */
    public function testLegacyRecycleBinItemWithoutCustomSettingsRegeneratesDerivedSettings(): void
    {
        $asset = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $assetId = $asset->getId();
        $asset->getEmbeddedMetaData(true, false);
        $asset->setCustomSetting('document_page_count', 3);
        $asset->setCustomSetting('customSettingsTest', 'test');
        $asset->save();

        Item::create($asset, $this->user);
        $recycledItem = (new Item\Listing())->current();
        // replace the data of the recycle bin item by a dump in the legacy format
        Storage::get('recycle_bin')->write(
            $recycledItem->getStorageFile(),
            TestHelper::getLegacyDumpDataWithoutCustomSettings($asset)
        );
        $asset->delete();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $recycledItem->restore();
        // the derived settings are unknown, so the restored data is processed again ...
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $restoredAsset);
        $this->assertNull($restoredAsset->getPageCount());
        $this->assertNull($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
        // the dump doesn't contain any custom settings, and no custom settings are loaded from anywhere else
        $this->assertNull($restoredAsset->getCustomSetting('customSettingsTest'));

        // ... which generates the derived settings again (with exiftool if available, so only a key
        // available with and without exiftool is checked)
        TestHelper::runAssetUpdateTasks($assetId);

        $restoredAsset = Asset::getById($assetId, ['force' => true]);
        $this->assertTrue($restoredAsset->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $restoredAsset->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * Verifies that object with children can be moved to recyclebin and restored
     *
     */
    public function testRecursiveObjectRecycleAndRestore(): void
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
        $recycledItems->setCondition('`path` = ?', $parentPath);

        $this->assertEquals(2, $recycledItems->current()->getAmount(), 'Expected 2 recycled item');

        $storage = Storage::get('recycle_bin');
        //recycle bin item storage file
        $recycledContent = unserialize($storage->read($recycledItems->current()->getStorageFile()));

        $this->assertEquals($parentId, $recycledContent->getId(), 'Expected recycled parent object ID');
        $this->assertCount(1, $recycledContent->getChildren(DataObject::$types, true)->getData(), 'Expected recycled child object');

        //restore deleted items (parent + child)
        $recycledItems->current()->restore();

        $restoredParent = DataObject::getById($parentId);
        $restoredChild = DataObject::getById($childId);

        $this->assertIsObject($restoredParent, 'Expected restored parent object');
        $this->assertIsObject($restoredChild, 'Expected restored child object');
    }

    /**
     * Verifies that an object data is restored properly
     *
     */
    public function testObjectDataRecycleAndRestore(): void
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
        $recycledItems->setCondition('`path` = ?', $sourceObjectPath);
        $recycledItems->current()->restore();

        //load relation and check if relation loads correctly
        $restoredSourceObject = DataObject::getById($sourceObjectId);
        $restoredRelation = $restoredSourceObject->getLobjects();
        $restoredLocalizedRelation = $restoredSourceObject->getLobjects();

        $this->assertEquals($inputText, $restoredSourceObject->getInput(), 'Input data not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredRelation[0]->getId(), 'Simple object relation not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredLocalizedRelation[0]->getId(), 'Localized object relation not restored properly');
    }
}
