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

namespace Pimcore\Tests\Model\DataType;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use DatePeriod;
use Exception;
use Pimcore\Cache;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\unittestBlock;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
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
     * Every geo datatype must round-trip inside a Block. Geo values are stored fully normalized
     * (plain arrays / JSON) and rebuilt by each sub-field's denormalize(), so they are the subset
     * of block child types that survives even a restricted unserialize().
     *
     * @throws Exception
     */
    public function testGeoDataTypesInsideBlock(): void
    {
        $point = new GeoCoordinates(48.208174, 16.373819);
        $bounds = new Geobounds(new GeoCoordinates(48.3, 16.5), new GeoCoordinates(48.1, 16.2));
        $polygon = [
            new GeoCoordinates(48.1, 16.1),
            new GeoCoordinates(48.2, 16.2),
            new GeoCoordinates(48.3, 16.3),
        ];
        $polyline = [
            new GeoCoordinates(47.0, 15.0),
            new GeoCoordinates(47.5, 15.5),
        ];

        $object = $this->createBlockObject();
        $object->setTestblock([
            [
                'blockgeopoint' => new BlockElement('blockgeopoint', 'geopoint', $point),
                'blockgeobounds' => new BlockElement('blockgeobounds', 'geobounds', $bounds),
                'blockgeopolygon' => new BlockElement('blockgeopolygon', 'geopolygon', $polygon),
                'blockgeopolyline' => new BlockElement('blockgeopolyline', 'geopolyline', $polyline),
            ],
        ]);
        $object->save();

        // Force-reload from the database so the block goes through the resource unserialize path.
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $data = $reloaded->getTestblock()[0];

        $loadedPoint = $data['blockgeopoint']->getData();
        $this->assertInstanceOf(GeoCoordinates::class, $loadedPoint);
        $this->assertEqualsWithDelta(48.208174, $loadedPoint->getLatitude(), 1e-6);
        $this->assertEqualsWithDelta(16.373819, $loadedPoint->getLongitude(), 1e-6);

        $loadedBounds = $data['blockgeobounds']->getData();
        $this->assertInstanceOf(Geobounds::class, $loadedBounds);
        $this->assertEqualsWithDelta(48.3, $loadedBounds->getNorthEast()->getLatitude(), 1e-6);
        $this->assertEqualsWithDelta(16.2, $loadedBounds->getSouthWest()->getLongitude(), 1e-6);

        $loadedPolygon = $data['blockgeopolygon']->getData();
        $this->assertIsArray($loadedPolygon);
        $this->assertCount(3, $loadedPolygon);
        $this->assertContainsOnlyInstancesOf(GeoCoordinates::class, $loadedPolygon);
        $this->assertEqualsWithDelta(48.3, $loadedPolygon[2]->getLatitude(), 1e-6);

        $loadedPolyline = $data['blockgeopolyline']->getData();
        $this->assertIsArray($loadedPolyline);
        $this->assertCount(2, $loadedPolyline);
        $this->assertContainsOnlyInstancesOf(GeoCoordinates::class, $loadedPolyline);
        $this->assertEqualsWithDelta(15.5, $loadedPolyline[1]->getLongitude(), 1e-6);
    }

    /**
     * Regression test for pimcore/platform-version#262.
     *
     * Unlike geo values, several block child types are stored as *live PHP objects* inside the
     * block's serialized resource blob: their block marshaller rebuilds a value object before
     * Serialize::serialize() (externalImage, consent, date, datetime, structuredTable), or their
     * normalize() keeps one (dateRange -> Carbon, via CarbonPeriod::toArray()).
     *
     * Block::getDataFromResource() must therefore allow object deserialization. Restricting it
     * silently dropped every value below to null, and the next save persisted that loss.
     *
     * @throws Exception
     */
    public function testObjectValueTypesInsideBlock(): void
    {
        $externalImage = new ExternalImage('https://example.com/image.png');
        $consent = new Consent(true, null);
        $date = new Carbon('2026-03-04 00:00:00');
        $dateTime = new Carbon('2026-05-06 07:08:09');

        $structuredTable = new StructuredTable();
        $structuredTable->setData([
            'row1' => ['col1' => 42, 'col2' => 'first'],
            'row2' => ['col1' => 43, 'col2' => 'second'],
        ]);

        $object = $this->createBlockObject();
        $object->setTestblock([
            [
                'blockexternalImage' => new BlockElement('blockexternalImage', 'externalImage', $externalImage),
                'blockconsent' => new BlockElement('blockconsent', 'consent', $consent),
                'blockdate' => new BlockElement('blockdate', 'date', $date),
                'blockdatetime' => new BlockElement('blockdatetime', 'datetime', $dateTime),
                'blockstructuredTable' => new BlockElement('blockstructuredTable', 'structuredTable', $structuredTable),
            ],
        ]);
        $object->save();

        // Force-reload from the database so the block goes through the resource unserialize path.
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $data = $reloaded->getTestblock()[0];

        $loadedExternalImage = $data['blockexternalImage']->getData();
        $this->assertInstanceOf(ExternalImage::class, $loadedExternalImage);
        $this->assertSame('https://example.com/image.png', $loadedExternalImage->getUrl());

        $loadedConsent = $data['blockconsent']->getData();
        $this->assertInstanceOf(Consent::class, $loadedConsent);
        $this->assertTrue($loadedConsent->getConsent());

        $loadedDate = $data['blockdate']->getData();
        $this->assertInstanceOf(CarbonInterface::class, $loadedDate);
        $this->assertSame($date->getTimestamp(), $loadedDate->getTimestamp());

        $loadedDateTime = $data['blockdatetime']->getData();
        $this->assertInstanceOf(CarbonInterface::class, $loadedDateTime);
        $this->assertSame($dateTime->getTimestamp(), $loadedDateTime->getTimestamp());

        $loadedStructuredTable = $data['blockstructuredTable']->getData();
        $this->assertInstanceOf(StructuredTable::class, $loadedStructuredTable);
        $this->assertSame('first', $loadedStructuredTable->getData()['row1']['col2']);
    }

    /**
     * Regression test for pimcore/platform-version#262, kept separate because a dateRange fails
     * louder than the rest: DateRange::normalize() returns CarbonPeriod::toArray(), i.e. an array
     * of Carbon objects, and denormalize() feeds them straight back into CarbonPeriod::create().
     * With object deserialization restricted, that threw InvalidPeriodParameterException during
     * save instead of merely losing the value.
     *
     * @throws Exception
     */
    public function testDateRangeInsideBlock(): void
    {
        $dateRange = CarbonPeriod::create('2026-01-01', '2026-01-05');

        $object = $this->createBlockObject();
        $object->setTestblock([
            [
                'blockdateRange' => new BlockElement('blockdateRange', 'dateRange', $dateRange),
            ],
        ]);
        $object->save();

        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $loaded = $reloaded->getTestblock()[0]['blockdateRange']->getData();

        // DatePeriod, not CarbonPeriod: the deep copy on the load path runs myclabs/deep-copy's
        // DatePeriodFilter, which rebuilds any DatePeriod subclass as a plain DatePeriod. That
        // downgrade is pre-existing and unrelated to the unserialize behaviour asserted here.
        $this->assertInstanceOf(DatePeriod::class, $loaded);
        $this->assertSame($dateRange->getStartDate()->getTimestamp(), $loaded->getStartDate()->getTimestamp());
        $this->assertSame($dateRange->getEndDate()->getTimestamp(), $loaded->getEndDate()->getTimestamp());
    }

    /**
     * Regression test for pimcore/platform-version#262, one nesting level deeper: a block inside
     * localizedfields routes its children through BlockDataMarshaller\Localizedfields, which
     * delegates to the same per-type block marshallers and therefore stores the same objects.
     *
     * @throws Exception
     */
    public function testObjectValueTypesInsideLocalizedBlock(): void
    {
        $externalImage = new ExternalImage('https://example.com/localized.png');
        $date = new Carbon('2026-07-08 00:00:00');

        $object = $this->createBlockObject();
        $object->setLtestblock([
            [
                'lblockexternalImage' => new BlockElement('lblockexternalImage', 'externalImage', $externalImage),
                'lblockdate' => new BlockElement('lblockdate', 'date', $date),
            ],
        ], 'en');
        $object->save();

        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $data = $reloaded->getLtestblock('en')[0];

        $loadedExternalImage = $data['lblockexternalImage']->getData();
        $this->assertInstanceOf(ExternalImage::class, $loadedExternalImage);
        $this->assertSame('https://example.com/localized.png', $loadedExternalImage->getUrl());

        $loadedDate = $data['lblockdate']->getData();
        $this->assertInstanceOf(CarbonInterface::class, $loadedDate);
        $this->assertSame($date->getTimestamp(), $loadedDate->getTimestamp());
    }

    /**
     * Regression test for pimcore/platform-version#262 / #298.
     *
     * A hotspotimage has no block marshaller, so Block stores the raw normalize() output — which
     * keeps the MarkerHotspotItem objects of its hotspot/marker metadata. Restricting the block
     * unserialize left __PHP_Incomplete_Class instances buried inside the reconstructed value:
     * denormalize() still returned a Hotspotimage, so the corruption stayed invisible until
     * something accessed the metadata (see Document\Editable\Image::getCacheTags()).
     *
     * @throws Exception
     */
    public function testHotspotImageMetaDataInsideBlock(): void
    {
        $image = TestHelper::createImageAsset();

        // Build it the way the editmode does: getDataFromEditmode() wraps each metadata entry in a
        // MarkerHotspotItem, and those objects are what end up in the block's serialized blob.
        $fieldDefinition = new DataObject\ClassDefinition\Data\Hotspotimage();
        $fieldDefinition->setName('blockhotspotimage');
        $hotspotImage = $fieldDefinition->getDataFromEditmode([
            'id' => $image->getId(),
            'hotspots' => [
                [
                    'name' => 'hotspot1',
                    'width' => 10,
                    'height' => 20,
                    'top' => 30,
                    'left' => 40,
                    'data' => [
                        ['name' => 'metaName', 'type' => 'text', 'value' => 'metaValue'],
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(Hotspotimage::class, $hotspotImage);
        $this->assertInstanceOf(MarkerHotspotItem::class, $hotspotImage->getHotspots()[0]['data'][0]);

        $object = $this->createBlockObject();
        $object->setTestblock([
            [
                'blockhotspotimage' => new BlockElement('blockhotspotimage', 'hotspotimage', $hotspotImage),
            ],
        ]);
        $object->save();

        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $loaded = $reloaded->getTestblock()[0]['blockhotspotimage']->getData();

        $this->assertInstanceOf(Hotspotimage::class, $loaded);

        $metaData = $loaded->getHotspots()[0]['data'][0];
        $this->assertNotInstanceOf(
            '__PHP_Incomplete_Class',
            $metaData,
            'hotspot metadata must not be neutralised by the block resource unserialize()'
        );
        // MarkerHotspotItem is ArrayAccess; array access is exactly what broke on the stripped object.
        $this->assertSame('metaName', $metaData['name']);
        $this->assertSame('metaValue', $metaData['value']);
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
