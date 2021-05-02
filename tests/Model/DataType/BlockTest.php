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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Cache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\DataObject\unittestBlock;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

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

    protected function setUpTestClasses()
    {
        $this->tester->setupPimcoreClass_Block();
    }

    /**
     * @return Unittest
     *
     * @throws \Exception
     */
    protected function createBlockObject()
    {
        /** @var Unittest $object */
        $object = new unittestBlock();
        $object->setParent(Service::createFolderByPath('/blocks'));
        $object->setKey('block1');
        $object->setPublished(true);

        return $object;
    }

    /**
     * @param $document
     *
     * @return Link
     */
    protected function createLinkData($document)
    {
        $link = new Link();
        $link->setPath($document);

        return $link;
    }

    /**
     * @param $image
     *
     * @return Hotspotimage
     */
    protected function createHotspotImage($image)
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
        $hotspotImage = new Hotspotimage($image, $hotspots);

        return $hotspotImage;
    }

    /**
     * Verifies that references are saved and fetched properly inside Block
     *
     * @throws \Exception
     */
    public function testReferencesInsideBlock()
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

        Cache\Runtime::clear();

        //reload from cache and save again
        $objectRef = DataObject::getById($object->getId());
        $objectRef->save(); //block data should retain here

        //reload from db
        $object = DataObject::getById($objectRef->getId(), true);

        $loadedData = $object->getTestblock();

        $loadedLink = $loadedData[0]['blocklink']->getData();
        $this->assertEquals($targetDocument->getId(), $loadedLink->getObject()->getId());

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
     * @throws \Exception
     */
    public function testReferencesInsideLocalizedBlock()
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

        Cache\Runtime::clear();

        //reload from cache and save again
        $objectRef = DataObject::getById($object->getId());
        $objectRef->save(); //block data should retain here

        //reload from db
        $object = DataObject::getById($objectRef->getId(), true);
        $loadedData = $object->getLtestblock('de');

        $loadedLink = $loadedData[0]['lblocklink']->getData();
        $this->assertEquals($targetDocument->getId(), $loadedLink->getObject()->getId());

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
     * @throws \Exception
     */
    public function testBlockDataFromReferences()
    {
        $cacheEnabled = Cache::isEnabled();
        if (!$cacheEnabled) {
            Cache::enable();
            Cache::getHandler()->setHandleCli(true);
        }

        $reference = TestHelper::createEmptyObject();
        $source = $this->createBlockObject();
        $data = [
            'blockmanyToManyRelations' => new BlockElement('blockmanyToManyRelations', 'advancedManyToManyRelation', $reference),
        ];
        $source->setLtestblock([$data], 'de');
        $source->save();

        //link source on target
        $target = TestHelper::createEmptyObject();
        $target->setHref($source);
        $target->save(); //block data should retain here

        //update block element - manyToManyRelations
        $referenceNew = TestHelper::createEmptyObject();
        $source->getLtestblock('de')[0]['blockmanyToManyRelations']->setData($referenceNew);
        $source->save();

        //reload target and fetch source
        $target = DataObject::getById($target->getId(), true);
        $sourceFromRef = $target->getHref();

        $loadedReference = $sourceFromRef->getLtestblock('de')[0]['blockmanyToManyRelations']->getData();

        $this->assertEquals($referenceNew->getId(), $loadedReference->getId());

        if (!$cacheEnabled) {
            Cache::disable();
            Cache::getHandler()->setHandleCli(false);
        }
    }
}
