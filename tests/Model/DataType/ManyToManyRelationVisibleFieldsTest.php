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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\Metadata\Predefined;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group dataTypeLocal
 */
class ManyToManyRelationVisibleFieldsTest extends ModelTestCase
{
    private const PREDEFINED_METADATA = 'visibleFieldsTestCopyright';

    private const IMAGE_ONLY_METADATA = 'visibleFieldsTestImageOnly';

    /** @var Predefined[] */
    private array $predefinedMetadata = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->predefinedMetadata = [
            $this->createPredefinedMetadata(self::PREDEFINED_METADATA),
            $this->createPredefinedMetadata(self::IMAGE_ONLY_METADATA, 'image'),
        ];
    }

    public function tearDown(): void
    {
        foreach ($this->predefinedMetadata as $predefined) {
            $predefined->delete();
        }
        $this->predefinedMetadata = [];

        TestHelper::cleanUp();
        parent::tearDown();
    }

    private function createPredefinedMetadata(string $name, ?string $targetSubtype = null): Predefined
    {
        $predefined = Predefined::getByName($name) ?? Predefined::create();
        $predefined->setName($name);
        $predefined->setType('input');
        $predefined->setTargetSubtype($targetSubtype);
        $predefined->save();

        return $predefined;
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupPimcoreClass_Unittest();
    }

    private function createRelationTestObject(string $someAttribute): RelationTest
    {
        $object = new RelationTest();
        $object->setParentId(1);
        $object->setKey('visible-fields-' . uniqid());
        $object->setPublished(true);
        $object->setSomeAttribute($someAttribute);
        $object->save();

        return $object;
    }

    private function createAssetWithMetadata(string $copyright): Asset\Image
    {
        $asset = TestHelper::createImageAsset('visible-fields-');
        $asset->addMetadata(self::PREDEFINED_METADATA, 'input', $copyright);
        $asset->save();

        return $asset;
    }

    private function createMixedDefinition(): ManyToManyRelation
    {
        $fd = new ManyToManyRelation();
        $fd->setName('mixedRelation');
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'RelationTest']]);
        $fd->setAssetsAllowed(true)->setAssetTypes([]);
        $fd->setDocumentsAllowed(false);

        return $fd;
    }

    public function testSetVisibleFieldsNormalizesInput(): void
    {
        $fd = new ManyToManyRelation();

        $this->assertNull($fd->getVisibleFields());
        $this->assertSame([], $fd->getVisibleFieldNames());

        $fd->setVisibleFields(['someAttribute', 'filename']);
        $this->assertSame('someAttribute,filename', $fd->getVisibleFields());

        $fd->setVisibleFields(' someAttribute , filename,,filename ');
        $this->assertSame(['someAttribute', 'filename'], $fd->getVisibleFieldNames());

        $fd->setVisibleFields('');
        $this->assertNull($fd->getVisibleFields());
    }

    public function testAvailableVisibleFieldsAreTheUnionOfAllowedTypes(): void
    {
        $fd = $this->createMixedDefinition();

        $available = $fd->getAvailableVisibleFields();

        // object class field, including a localized one
        $this->assertArrayHasKey('someAttribute', $available);
        $this->assertSame('input', $available['someAttribute']['fieldtype']);
        $this->assertTrue($available['someAttribute']['noteditable']);
        $this->assertSame(['object:RelationTest'], $available['someAttribute']['sources']);

        $this->assertArrayHasKey('xsomeAttribute', $available);
        $this->assertTrue($available['xsomeAttribute']['localized']);

        // asset system property and predefined metadata
        $this->assertArrayHasKey('filename', $available);
        $this->assertSame(['asset'], $available['filename']['sources']);

        $this->assertArrayHasKey(self::PREDEFINED_METADATA, $available);
        $this->assertSame('input', $available[self::PREDEFINED_METADATA]['metadataType']);
        $this->assertSame(['asset'], $available[self::PREDEFINED_METADATA]['sources']);

        // shared element properties are attributed to every allowed element type, not to a class
        $this->assertArrayHasKey('creationDate', $available);
        $this->assertSame(['object', 'asset'], $available['creationDate']['sources']);

        // documents are not allowed, objects only offer their class fields
        $this->assertArrayNotHasKey('localizedfields', $available);
    }

    public function testAvailableVisibleFieldsFollowTheAllowedTypes(): void
    {
        $fd = new ManyToManyRelation();
        $fd->setObjectsAllowed(false)->setAssetsAllowed(false)->setDocumentsAllowed(true);

        $available = $fd->getAvailableVisibleFields();

        $this->assertArrayHasKey('creationDate', $available);
        $this->assertSame(['document'], $available['creationDate']['sources']);
        $this->assertArrayNotHasKey('filename', $available);
        $this->assertArrayNotHasKey('someAttribute', $available);

        $fd->setObjectsAllowed(true)->setClasses([]);
        $this->assertArrayNotHasKey('someAttribute', $fd->getAvailableVisibleFields(), 'objects without a class restriction contribute no class fields');
    }

    public function testObjectsWithoutClassRestrictionStillOfferTheCommonProperties(): void
    {
        $fd = new ManyToManyRelation();
        $fd->setObjectsAllowed(true)->setClasses([]);
        $fd->setAssetsAllowed(false)->setDocumentsAllowed(false);

        $available = $fd->getAvailableVisibleFields();

        $this->assertSame(['creationDate', 'modificationDate'], array_keys($available));
        $this->assertSame(['object'], $available['creationDate']['sources']);
        $this->assertSame('date', $available['modificationDate']['fieldtype']);

        $fd->setVisibleFields('creationDate');
        $fd->enrichLayoutDefinition(null);
        $this->assertSame(['object'], $fd->visibleFieldDefinitions['creationDate']['sources']);

        $object = $this->createRelationTestObject('any');
        $this->assertSame($object->getCreationDate(), $fd->getVisibleFieldData($object)['creationDate']);

        // a class field is not offered without a class restriction, so a configured name must not resolve either
        $fd->setVisibleFields('creationDate,someAttribute');
        $data = $fd->getVisibleFieldData($object);
        $this->assertSame($object->getCreationDate(), $data['creationDate']);
        $this->assertNull($data['someAttribute'], 'names that are not offered for the definition must not resolve');
    }

    public function testConfiguredNamesOnlyResolveForTheElementTypesTheyAreOfferedFor(): void
    {
        $object = $this->createRelationTestObject('object value');
        $asset = $this->createAssetWithMetadata('(c) pimcore');

        // objects restricted to another class: RelationTest fields are not offered, but common properties are
        $fd = new ManyToManyRelation();
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'unittest']]);
        $fd->setAssetsAllowed(true)->setAssetTypes([]);
        $fd->setVisibleFields(['someAttribute', 'creationDate', self::PREDEFINED_METADATA]);

        $data = $fd->getVisibleFieldData($object);
        $this->assertNull($data['someAttribute'], 'a field of a class that is not allowed must not resolve');
        $this->assertSame($object->getCreationDate(), $data['creationDate']);
        $this->assertNull($data[self::PREDEFINED_METADATA]);

        $data = $fd->getVisibleFieldData($asset);
        $this->assertSame('(c) pimcore', $data[self::PREDEFINED_METADATA]);
        $this->assertSame($asset->getCreationDate(), $data['creationDate']);

        // assets not allowed at all: asset metadata is not offered and therefore not resolved
        $fd->setAssetsAllowed(false);
        $this->assertNull($fd->getVisibleFieldData($asset)[self::PREDEFINED_METADATA]);
    }

    public function testEnrichLayoutDefinitionResolvesConfiguredFields(): void
    {
        $fd = $this->createMixedDefinition();
        $fd->setVisibleFields('someAttribute,filename,doesNotExist');

        $fd->enrichLayoutDefinition(null);

        $definitions = $fd->visibleFieldDefinitions;
        $this->assertSame(['someAttribute', 'filename', 'doesNotExist'], array_keys($definitions));
        $this->assertSame('input', $definitions['someAttribute']['fieldtype']);
        $this->assertSame(['object:RelationTest'], $definitions['someAttribute']['sources']);
        $this->assertSame(['asset'], $definitions['filename']['sources']);

        // unknown fields fall back to a read-only input so the column is still rendered
        $this->assertSame('input', $definitions['doesNotExist']['fieldtype']);
        $this->assertTrue($definitions['doesNotExist']['noteditable']);
        $this->assertSame([], $definitions['doesNotExist']['sources']);

        $fd->setVisibleFields(null);
        $fd->enrichLayoutDefinition(null);
        $this->assertSame([], $fd->visibleFieldDefinitions);
    }

    public function testVisibleFieldDataIsResolvedPerElementType(): void
    {
        $object = $this->createRelationTestObject('object value');
        $asset = $this->createAssetWithMetadata('(c) pimcore');

        $fd = $this->createMixedDefinition();
        $fd->setVisibleFields(['someAttribute', 'filename', self::PREDEFINED_METADATA]);

        $objectData = $fd->getVisibleFieldData($object);
        $this->assertSame('object value', $objectData['someAttribute']);
        $this->assertNull($objectData['filename']);
        $this->assertNull($objectData[self::PREDEFINED_METADATA]);

        $assetData = $fd->getVisibleFieldData($asset);
        $this->assertNull($assetData['someAttribute']);
        $this->assertSame($asset->getFilename(), $assetData['filename']);
        $this->assertSame('(c) pimcore', $assetData[self::PREDEFINED_METADATA]);
    }

    public function testAssetMetadataIsOnlyResolvedWhereItApplies(): void
    {
        $image = TestHelper::createImageAsset('visible-fields-');
        $image->addMetadata(self::IMAGE_ONLY_METADATA, 'input', 'image value');
        // ad-hoc metadata named like a class field of the allowed object class
        $image->addMetadata('someAttribute', 'input', 'ad-hoc collision');
        $image->save();

        $video = TestHelper::createVideoAsset('visible-fields-');
        $video->addMetadata(self::IMAGE_ONLY_METADATA, 'input', 'video value');
        $video->save();

        $fd = $this->createMixedDefinition();
        $fd->setVisibleFields(['someAttribute', self::IMAGE_ONLY_METADATA, self::PREDEFINED_METADATA]);

        $available = $fd->getAvailableVisibleFields();
        $this->assertSame(['asset'], $available[self::IMAGE_ONLY_METADATA]['sources']);
        $this->assertSame(['object:RelationTest'], $available['someAttribute']['sources']);

        $imageData = $fd->getVisibleFieldData($image);
        $this->assertSame('image value', $imageData[self::IMAGE_ONLY_METADATA]);
        $this->assertNull($imageData['someAttribute'], 'a class field must not surface same-named ad-hoc asset metadata');
        $this->assertNull($imageData[self::PREDEFINED_METADATA], 'predefined metadata that is not set on the asset resolves to null');

        $videoData = $fd->getVisibleFieldData($video);
        $this->assertNull($videoData[self::IMAGE_ONLY_METADATA], 'metadata defined for images must not be shown on a video');
        $this->assertNull($videoData['someAttribute']);

        // restricting the allowed asset types removes metadata of other subtypes from the offered fields
        $fd->setAssetTypes([['assetTypes' => 'video']]);
        $available = $fd->getAvailableVisibleFields();
        $this->assertArrayNotHasKey(self::IMAGE_ONLY_METADATA, $available);
        $this->assertArrayHasKey(self::PREDEFINED_METADATA, $available);
    }

    public function testPredefinedMetadataChangesAreReflectedWithinTheSameRequest(): void
    {
        $fd = $this->createMixedDefinition();
        $this->assertArrayHasKey(self::PREDEFINED_METADATA, $fd->getAvailableVisibleFields());
        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields());

        $late = $this->createPredefinedMetadata('visibleFieldsTestLateArrival');

        try {
            $this->assertArrayHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields(), 'a definition saved after the first lookup must be offered');

            $asset = TestHelper::createImageAsset('visible-fields-');
            $asset->addMetadata('visibleFieldsTestLateArrival', 'input', 'late value');
            $asset->save();
            $fd->setVisibleFields('visibleFieldsTestLateArrival');
            $this->assertSame('late value', $fd->getVisibleFieldData($asset)['visibleFieldsTestLateArrival']);
        } finally {
            $late->delete();
        }

        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields(), 'a deleted definition must no longer be offered');
        $this->assertNull($fd->getVisibleFieldData($asset)['visibleFieldsTestLateArrival'], 'a deleted definition must no longer resolve');
    }

    public function testAdvancedRelationEditmodeRowsContainVisibleFieldData(): void
    {
        $object = $this->createRelationTestObject('object value');
        $asset = $this->createAssetWithMetadata('(c) pimcore');

        $fd = new AdvancedManyToManyRelation();
        $fd->setName('advancedMixedRelation');
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'RelationTest']]);
        $fd->setAssetsAllowed(true)->setAssetTypes([]);
        $fd->setColumns([['position' => 1, 'key' => 'filename', 'type' => 'text', 'label' => 'Filename (meta)']]);
        $fd->setVisibleFields('someAttribute,filename,' . self::PREDEFINED_METADATA);

        $objectMetadata = new ElementMetadata('advancedMixedRelation', ['filename'], $object);
        $objectMetadata->setFilename('meta value');
        $assetMetadata = new ElementMetadata('advancedMixedRelation', ['filename'], $asset);

        $rows = $fd->getDataForEditmode([$objectMetadata, $assetMetadata]);

        $this->assertCount(2, $rows);

        $this->assertSame($object->getId(), $rows[0]['id']);
        $this->assertSame('object value', $rows[0]['someAttribute']);
        $this->assertNull($rows[0][self::PREDEFINED_METADATA]);
        $this->assertSame('meta value', $rows[0]['filename'], 'metadata columns take precedence over visible fields');

        $this->assertSame($asset->getId(), $rows[1]['id']);
        $this->assertNull($rows[1]['someAttribute']);
        $this->assertSame('(c) pimcore', $rows[1][self::PREDEFINED_METADATA]);
        $this->assertNull($rows[1]['filename'], 'metadata columns take precedence over visible fields');

        // with optimized admin loading the values are left to the UI to fetch asynchronously
        $fd->setOptimizedAdminLoading(true);
        $rows = $fd->getDataForEditmode([$assetMetadata]);
        $this->assertArrayNotHasKey('someAttribute', $rows[0]);
        $this->assertArrayNotHasKey(self::PREDEFINED_METADATA, $rows[0]);
        $fd->setOptimizedAdminLoading(false);

        $fd->setVisibleFields(null);
        $rows = $fd->getDataForEditmode([$assetMetadata]);
        $this->assertArrayNotHasKey('someAttribute', $rows[0]);
        $this->assertArrayNotHasKey(self::PREDEFINED_METADATA, $rows[0]);
    }

    public function testVisibleFieldsAreSynchronizedWithMainDefinition(): void
    {
        $main = new ManyToManyRelation();
        $main->setVisibleFields('someAttribute,filename');

        $fd = new ManyToManyRelation();
        $fd->synchronizeWithMainDefinition($main);
        $this->assertSame('someAttribute,filename', $fd->getVisibleFields());

        $advancedMain = new AdvancedManyToManyRelation();
        $advancedMain->setColumns([]);
        $advancedMain->setVisibleFields('filename');

        $advanced = new AdvancedManyToManyRelation();
        $advanced->synchronizeWithMainDefinition($advancedMain);
        $this->assertSame('filename', $advanced->getVisibleFields());
    }
}
