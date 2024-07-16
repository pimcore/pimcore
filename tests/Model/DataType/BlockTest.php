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

namespace Pimcore\Tests\Model\DataType;

use Exception;
use Pimcore\Cache;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\unittestBlock;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class BlockTest
 *
 * @group model.datatype.block
 */
class BlockTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_Block();
    }

    /**
     *
     * @throws Exception
     */
    protected function createBlockObject(): unittestBlock
    {
        $object = new unittestBlock();
        $object->setParent(Service::createFolderByPath('/blocks'));
        $object->setKey('block1');
        $object->setPublished(true);

        return $object;
    }

    protected function createLinkData(Page $document): Link
    {
        $link = new Link();
        $link->setPath($document->getFullPath());

        return $link;
    }

    protected function createHotspotImage(Image $image): Hotspotimage
    {
        $hotspot1 = [
            'name' => 'hotspot1',
            'width' => 10,
            'height' => 20,
            'top' => 30,
            'left' => 40,
        ];
        $hotspots[] = $hotspot1;

        $hotspot2 = [
            'name' => 'hotspot2',
            'width' => 10,
            'height' => 50,
            'top' => 20,
            'left' => 40,
        ];

        $hotspots[] = $hotspot2;

        return new Hotspotimage($image, $hotspots);
    }

    /**
     * Verifies that references are saved and fetched properly inside Block
     *
     * @throws Exception
     */
    public function testReferencesInsideBlock(): void
    {
        $cacheEnabled = Cache::isEnabled();
        if (!$cacheEnabled) {
            Cache::enable();
            Cache::getHandler()->setHandleCli(true);
        }

        $targetDocument = TestHelper::createEmptyDocumentPage();
        $asset = TestHelper::createImageAsset('', null, true);

        $object = $this->createBlockObject();
        $link = $this->createLinkData($targetDocument);
        $hotspotImage = $this->createHotspotImage($asset);

        $data = [
            'blockinput' => new BlockElement('blockinput', 'input', 'test-input'),
            'blocklink' => new BlockElement('blocklink', 'input', $link),
            'blockhotspotimage' => new BlockElement('blockhotspotimage', 'hotspotimage', $hotspotImage),
        ];
        $object->setTestblock([$data]);
        $object->save();

        Cache\RuntimeCache::clear();

        //reload from cache and save again
        $objectRef = DataObject::getById($object->getId());
        $objectRef->save(); //block data should retain here

        //reload from db
        $object = DataObject::getById($objectRef->getId(), ['force' => true]);

        $loadedData = $object->getTestblock();

        /** @var Link $loadedLink */
        $loadedLink = $loadedData[0]['blocklink']->getData();
        $this->assertEquals($targetDocument->getId(), $loadedLink->getElement()->getId());

        $loadedHotspotImage = $loadedData[0]['blockhotspotimage']->getData();
        $this->assertEquals($asset->getId(), $loadedHotspotImage->getImage()->getId());

        if (!$cacheEnabled) {
            Cache::disable();
            Cache::getHandler()->setHandleCli(false);
        }
    }

    /**
     * Verifies that references are saved and fetched properly inside Localized Block
     *
     * @throws Exception
     */
    public function testReferencesInsideLocalizedBlock(): void
    {
        $cacheEnabled = Cache::isEnabled();
        if (!$cacheEnabled) {
            Cache::enable();
            Cache::getHandler()->setHandleCli(true);
        }

        $targetDocument = TestHelper::createEmptyDocumentPage();
        $asset = TestHelper::createImageAsset('', null, true);

        $object = $this->createBlockObject();
        $link = $this->createLinkData($targetDocument);
        $hotspotImage = $this->createHotspotImage($asset);

        $data = [
            'lblockinput' => new BlockElement('lblockinput', 'input', 'test-input'),
            'lblocklink' => new BlockElement('lblocklink', 'input', $link),
            'lblockhotspotimage' => new BlockElement('lblockhotspotimage', 'hotspotimage', $hotspotImage),
        ];
        $object->setLtestblock([$data], 'de');
        $object->save();

        Cache\RuntimeCache::clear();

        //reload from cache and save again
        $objectRef = DataObject::getById($object->getId());
        $objectRef->save(); //block data should retain here

        //reload from db
        $object = DataObject::getById($objectRef->getId(), ['force' => true]);
        $loadedData = $object->getLtestblock('de');

        /** @var Link $loadedLink */
        $loadedLink = $loadedData[0]['lblocklink']->getData();
        $this->assertEquals($targetDocument->getId(), $loadedLink->getElement()->getId());

        $loadedHotspotImage = $loadedData[0]['lblockhotspotimage']->getData();
        $this->assertEquals($asset->getId(), $loadedHotspotImage->getImage()->getId());

        if (!$cacheEnabled) {
            Cache::disable();
            Cache::getHandler()->setHandleCli(false);
        }
    }

    /**
     * Verifies that Block data is loaded correctly from relations
     *
     * @throws Exception
     */
    public function testBlockDataFromReferences(): void
    {
        $cacheEnabled = Cache::isEnabled();
        if (!$cacheEnabled) {
            Cache::enable();
            Cache::getHandler()->setHandleCli(true);
        }

        $reference = TestHelper::createEmptyObject();
        $source = $this->createBlockObject();
        $data = [
            'lblockadvancedRelations' => new BlockElement('lblockadvancedRelations', 'advancedManyToManyRelation', [new DataObject\Data\ElementMetadata('lblockadvancedRelations', [], $reference)]),
        ];
        $source->setLtestblock([$data], 'de');
        $source->save();

        //link source on target
        $target = TestHelper::createEmptyObject();
        $target->setHref($source);
        $target->save(); //block data should retain here

        //update block element - manyToManyRelations
        $referenceNew = TestHelper::createEmptyObject();
        $source->getLtestblock('de')[0]['lblockadvancedRelations']->setData([new DataObject\Data\ElementMetadata('lblockadvancedRelations', [], $referenceNew)]);
        $source->save();

        //reload target and fetch source
        $target = DataObject::getById($target->getId(), ['force' => true]);
        $sourceFromRef = $target->getHref();

        $loadedReference = $sourceFromRef->getLtestblock('de')[0]['lblockadvancedRelations']->getData();

        $this->assertEquals($referenceNew->getId(), $loadedReference[0]->getElement()->getId());

        if (!$cacheEnabled) {
            Cache::disable();
            Cache::getHandler()->setHandleCli(false);
        }
    }
}
