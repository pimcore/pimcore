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
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\unittestBlock;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;

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
        $this->tester->setupPimcoreClass_RelationTest();
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

        // The concrete class depends on the Carbon major, which is not pinned (^2.72 || ^3.10):
        // Carbon 3's CarbonPeriod extends DatePeriod, so the deep copy on the load path runs
        // myclabs/deep-copy's DatePeriodFilter and rebuilds it as a plain DatePeriod; Carbon 2's
        // CarbonPeriod does not, so it stays a CarbonPeriod. Both are fine and neither is what this
        // test is about - assert the period survived with its bounds instead.
        $this->assertTrue(
            $loaded instanceof DatePeriod || $loaded instanceof CarbonPeriod,
            'expected a date period, got ' . get_debug_type($loaded)
        );
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
     * Hotspot/marker metadata of type asset/document/object holds the referenced element itself
     * (see Hotspotimage::getDataFromEditmode()). Inside a block that element must not be serialized
     * into the resource blob, because Block::getDataFromResource() only allows a fixed set of value
     * object classes on unserialize() (GHSA-6324-x2cv-h975); the block marshaller stores a
     * type/id reference instead and resolves it again on load.
     *
     * @throws Exception
     */
    public function testHotspotImageElementMetaDataInsideBlock(): void
    {
        $image = TestHelper::createImageAsset();
        $linkedAsset = TestHelper::createImageAsset('', null, true);
        $linkedObject = TestHelper::createEmptyObject();

        $hotspotImage = $this->createHotspotImageWithElementMetaData($image, $linkedAsset, $linkedObject);
        $this->assertInstanceOf(Asset::class, $hotspotImage->getHotspots()[0]['data'][0]->getValue());

        $object = $this->createBlockObject();
        $blockData = [
            [
                'blockhotspotimage' => new BlockElement('blockhotspotimage', 'hotspotimage', $hotspotImage),
            ],
        ];
        $object->setTestblock($blockData);

        /** @var DataObject\ClassDefinition\Data\Block $blockDefinition */
        $blockDefinition = $object->getClass()->getFieldDefinition('testblock');
        $resourceData = $blockDefinition->getDataForResource($blockData, $object, ['owner' => $object, 'fieldname' => 'testblock']);
        $this->assertFalse(
            str_contains($resourceData, $this->serializedObjectHeader($linkedAsset)),
            'the referenced asset must be stored by id, not as a serialized object'
        );
        $this->assertFalse(
            str_contains($resourceData, $this->serializedObjectHeader($linkedObject)),
            'the referenced object must be stored by id, not as a serialized object'
        );
        $this->assertFalse(
            str_contains($resourceData, $this->serializedObjectHeader(new MarkerHotspotItem([]))),
            'hotspot metadata must be stored as plain arrays'
        );

        $object->save();

        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $loaded = $reloaded->getTestblock()[0]['blockhotspotimage']->getData();
        $this->assertInstanceOf(Hotspotimage::class, $loaded);
        $this->assertSame($image->getId(), $loaded->getImage()->getId());
        $this->assertElementMetaDataResolved($loaded, $linkedAsset, $linkedObject);
    }

    /**
     * Same as testHotspotImageElementMetaDataInsideBlock() for an image gallery, whose items are
     * hotspot images.
     *
     * @throws Exception
     */
    public function testImageGalleryElementMetaDataInsideBlock(): void
    {
        $image = TestHelper::createImageAsset();
        $linkedAsset = TestHelper::createImageAsset('', null, true);
        $linkedObject = TestHelper::createEmptyObject();

        $gallery = new ImageGallery([
            $this->createHotspotImageWithElementMetaData($image, $linkedAsset, $linkedObject),
        ]);

        $object = $this->createBlockObject();
        $blockData = [
            [
                'blockimagegallery' => new BlockElement('blockimagegallery', 'imageGallery', $gallery),
            ],
        ];
        $object->setTestblock($blockData);

        /** @var DataObject\ClassDefinition\Data\Block $blockDefinition */
        $blockDefinition = $object->getClass()->getFieldDefinition('testblock');
        $resourceData = $blockDefinition->getDataForResource($blockData, $object, ['owner' => $object, 'fieldname' => 'testblock']);
        $this->assertFalse(
            str_contains($resourceData, $this->serializedObjectHeader($linkedAsset)),
            'the referenced asset must be stored by id, not as a serialized object'
        );

        $object->save();

        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $loaded = $reloaded->getTestblock()[0]['blockimagegallery']->getData();
        $this->assertInstanceOf(ImageGallery::class, $loaded);
        $this->assertCount(1, $loaded->getItems());
        $this->assertElementMetaDataResolved($loaded->getItems()[0], $linkedAsset, $linkedObject);
    }

    /**
     * Blocks saved before the hotspotimage block marshaller existed carry the referenced element
     * objects inside the serialized blob. unserialize() now turns those into __PHP_Incomplete_Class,
     * and the marshaller must resolve them back to the element by the class name and id they carry
     * instead of leaving an unusable stub (or null) in the hotspot metadata.
     *
     * @throws Exception
     */
    public function testLegacyBlockBlobWithElementObjectsInHotspotMetaData(): void
    {
        $image = TestHelper::createImageAsset();
        $linkedAsset = TestHelper::createImageAsset('', null, true);
        $linkedObject = TestHelper::createEmptyObject();

        $object = $this->createBlockObject();
        /** @var DataObject\ClassDefinition\Data\Block $blockDefinition */
        $blockDefinition = $object->getClass()->getFieldDefinition('testblock');

        // Exactly what Block::getDataForResource() wrote before: raw Hotspotimage::normalize() output,
        // with the loaded elements sitting inside the MarkerHotspotItem values.
        $legacyBlob = Serialize::serialize([
            [
                'blockhotspotimage' => [
                    'name' => 'blockhotspotimage',
                    'type' => 'hotspotimage',
                    'data' => [
                        'hotspots' => [
                            [
                                'name' => 'hotspot1',
                                'width' => 10,
                                'height' => 20,
                                'top' => 30,
                                'left' => 40,
                                'data' => [
                                    new MarkerHotspotItem(['name' => 'linkedAsset', 'type' => 'asset', 'value' => $linkedAsset]),
                                ],
                            ],
                        ],
                        'marker' => [
                            [
                                'name' => 'marker1',
                                'top' => 50,
                                'left' => 60,
                                'data' => [
                                    new MarkerHotspotItem(['name' => 'linkedObject', 'type' => 'object', 'value' => $linkedObject]),
                                ],
                            ],
                        ],
                        'crop' => [],
                        'image' => ['type' => 'asset', 'id' => $image->getId()],
                    ],
                ],
            ],
        ]);
        $this->assertTrue(str_contains($legacyBlob, $this->serializedObjectHeader($linkedAsset)));

        $loaded = $blockDefinition->getDataFromResource($legacyBlob, $object, ['owner' => $object, 'fieldname' => 'testblock']);

        $hotspotImage = $loaded[0]['blockhotspotimage']->getData();
        $this->assertInstanceOf(Hotspotimage::class, $hotspotImage);
        $this->assertSame($image->getId(), $hotspotImage->getImage()->getId());
        $this->assertElementMetaDataResolved($hotspotImage, $linkedAsset, $linkedObject);
    }

    private function createHotspotImageWithElementMetaData(Image $image, Asset $linkedAsset, DataObject\Concrete $linkedObject): Hotspotimage
    {
        // Built through the PHP API: MarkerHotspotItem accepts the referenced element itself as
        // value (constructor / setValue()), unlike the editmode path, which stores the element's id.
        return new Hotspotimage(
            $image,
            [
                [
                    'name' => 'hotspot1',
                    'width' => 10,
                    'height' => 20,
                    'top' => 30,
                    'left' => 40,
                    'data' => [
                        new MarkerHotspotItem(['name' => 'linkedAsset', 'type' => 'asset', 'value' => $linkedAsset]),
                        new MarkerHotspotItem(['name' => 'plainText', 'type' => 'text', 'value' => 'metaValue']),
                    ],
                ],
            ],
            [
                [
                    'name' => 'marker1',
                    'top' => 50,
                    'left' => 60,
                    'data' => [
                        new MarkerHotspotItem(['name' => 'linkedObject', 'type' => 'object', 'value' => $linkedObject]),
                    ],
                ],
            ]
        );
    }

    private function serializedObjectHeader(object $object): string
    {
        return 'O:' . strlen(get_class($object)) . ':"' . get_class($object) . '"';
    }

    private function assertElementMetaDataResolved(Hotspotimage $hotspotImage, Asset $linkedAsset, DataObject\Concrete $linkedObject): void
    {
        $hotspotMetaData = $hotspotImage->getHotspots()[0]['data'];
        $this->assertInstanceOf(MarkerHotspotItem::class, $hotspotMetaData[0]);
        $this->assertSame('asset', $hotspotMetaData[0]['type']);
        $this->assertInstanceOf(Asset::class, $hotspotMetaData[0]['value']);
        $this->assertSame($linkedAsset->getId(), $hotspotMetaData[0]['value']->getId());
        $this->assertSame($linkedAsset->getId(), $hotspotMetaData[0]->getValue(), 'the element is stored by id, as the editmode does');
        if (isset($hotspotMetaData[1])) {
            $this->assertSame('metaValue', $hotspotMetaData[1]['value']);
        }

        $markerMetaData = $hotspotImage->getMarker()[0]['data'];
        $this->assertInstanceOf(MarkerHotspotItem::class, $markerMetaData[0]);
        $this->assertSame('object', $markerMetaData[0]['type']);
        $this->assertInstanceOf(DataObject\Concrete::class, $markerMetaData[0]['value']);
        $this->assertSame($linkedObject->getId(), $markerMetaData[0]['value']->getId());
        $this->assertSame($linkedObject->getId(), $markerMetaData[0]->getValue(), 'the element is stored by id, as the editmode does');
    }

    /**
     * Regression test for GHSA-6324-x2cv-h975.
     *
     * Block::getDataFromResource() used to unserialize a block's resource blob with every class
     * allowed. Since this blob can be request-controlled wherever getDataFromResource() is
     * reachable (e.g. through the denylist-mode Twig sandbox, which permits calling it), a forged
     * resource string could instantiate arbitrary PHP objects and run POP-chain gadgets via
     * __wakeup()/__destruct(). Only the value object classes getDataForResource() can actually
     * produce (see Block::ALLOWED_RESOURCE_CLASSES) may now be reconstructed; everything else must
     * come back as __PHP_Incomplete_Class, which never has __wakeup() called on it.
     *
     * @throws Exception
     */
    public function testForgedResourceDataCannotWakeUpArbitraryObjects(): void
    {
        UnserializeWakeupProbe::$wokeUp = false;

        $forged = Serialize::serialize([
            [
                'forgedElement' => [
                    'name' => 'forgedElement',
                    'type' => 'input',
                    'data' => new UnserializeWakeupProbe(),
                ],
            ],
        ]);

        (new DataObject\ClassDefinition\Data\Block())->getDataFromResource($forged);

        $this->assertFalse(
            UnserializeWakeupProbe::$wokeUp,
            'Block::getDataFromResource() must not invoke __wakeup() on a class outside its resource allowlist'
        );
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

    /**
     * Verifies that relations inside a Block are rewritten by Service::rewriteIds
     * (used by "Paste recursive, updating references")
     *
     * @throws Exception
     */
    public function testRewriteIdsInsideBlock(): void
    {
        $oldTarget = $this->createRelationTestObject('rewrite-old-target');
        $newTarget = $this->createRelationTestObject('rewrite-new-target');

        $object = $this->createBlockObject();
        $data = [
            'blockinput' => new BlockElement('blockinput', 'input', 'test-input'),
            'blockadvancedRelations' => new BlockElement(
                'blockadvancedRelations',
                'advancedManyToManyRelation',
                [new DataObject\Data\ElementMetadata('blockadvancedRelations', [], $oldTarget)]
            ),
        ];
        $object->setTestblock([$data]);
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        Service::rewriteIds($object, ['object' => [$oldTarget->getId() => $newTarget->getId()]]);

        $rewritten = $object->getTestblock()[0]['blockadvancedRelations']->getData();
        $this->assertEquals($newTarget->getId(), $rewritten[0]->getElement()->getId());

        //rewritten reference should survive save & reload
        $object->save();
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $reloaded = $object->getTestblock()[0]['blockadvancedRelations']->getData();
        $this->assertEquals($newTarget->getId(), $reloaded[0]->getElement()->getId());
    }

    /**
     * Verifies that relations inside a Block nested in Localizedfields are rewritten
     * by Service::rewriteIds (used by "Paste recursive, updating references")
     *
     * @throws Exception
     */
    public function testRewriteIdsInsideLocalizedBlock(): void
    {
        $oldTarget = TestHelper::createEmptyObject();
        $newTarget = TestHelper::createEmptyObject();

        $object = $this->createBlockObject();
        $data = [
            'lblockadvancedRelations' => new BlockElement(
                'lblockadvancedRelations',
                'advancedManyToManyRelation',
                [new DataObject\Data\ElementMetadata('lblockadvancedRelations', [], $oldTarget)]
            ),
        ];
        $object->setLtestblock([$data], 'de');
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        Service::rewriteIds($object, ['object' => [$oldTarget->getId() => $newTarget->getId()]]);

        $rewritten = $object->getLtestblock('de')[0]['lblockadvancedRelations']->getData();
        $this->assertEquals($newTarget->getId(), $rewritten[0]->getElement()->getId());
    }

    protected function createRelationTestObject(string $key): DataObject\RelationTest
    {
        $object = new DataObject\RelationTest();
        $object->setParent(Service::createFolderByPath('__test/relationobjects'));
        $object->setKey($key);
        $object->setPublished(true);
        $object->save();

        return $object;
    }
}
