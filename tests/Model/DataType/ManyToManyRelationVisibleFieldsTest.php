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
use Pimcore\Model\DataObject;
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

        // reload: a definition saved from a fresh model does not know its storage location yet, so delete() on it
        // would silently delete nothing
        $reloaded = Predefined::getById((string) $predefined->getId());
        $this->assertInstanceOf(Predefined::class, $reloaded);

        return $reloaded;
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

        // the lightweight source map used for value resolution agrees with the described fields
        $this->assertSame(array_map(static fn (array $field): array => $field['sources'], $available), $fd->getVisibleFieldSources());
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

        // object folders are related elements of an unrestricted relation too and have the common properties
        $folder = DataObject\Service::createFolderByPath('/visible-fields-folder-' . uniqid());
        $this->assertInstanceOf(DataObject\Folder::class, $folder);
        $data = $fd->getVisibleFieldData($folder);
        $this->assertSame($folder->getCreationDate(), $data['creationDate']);
        $this->assertNull($data['someAttribute']);
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
        $this->assertArrayNotHasKey(self::IMAGE_ONLY_METADATA, $fd->getVisibleFieldSources(), 'the cached source map follows the allowed asset types');
        $this->assertNull($fd->getVisibleFieldData($image)[self::IMAGE_ONLY_METADATA]);
    }

    public function testPredefinedMetadataChangesAreReflectedWithinTheSameRequest(): void
    {
        $fd = $this->createMixedDefinition();
        $this->assertArrayHasKey(self::PREDEFINED_METADATA, $fd->getAvailableVisibleFields());
        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields());
        // prime the per-request source map, which must be invalidated by the save below
        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getVisibleFieldSources());

        $late = $this->createPredefinedMetadata('visibleFieldsTestLateArrival');

        try {
            $this->assertArrayHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields(), 'a definition saved after the first lookup must be offered');
            $this->assertSame(['asset'], $fd->getVisibleFieldSources()['visibleFieldsTestLateArrival'] ?? null, 'the cached source map must be invalidated by the save');

            $asset = TestHelper::createImageAsset('visible-fields-');
            $asset->addMetadata('visibleFieldsTestLateArrival', 'input', 'late value');
            $asset->save();
            $fd->setVisibleFields('visibleFieldsTestLateArrival');
            $this->assertSame('late value', $fd->getVisibleFieldData($asset)['visibleFieldsTestLateArrival']);
        } finally {
            $late->delete();
        }

        $this->assertNull(Predefined::getById((string) $late->getId()), 'the fixture must really be gone');
        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getAvailableVisibleFields(), 'a deleted definition must no longer be offered');
        $this->assertArrayNotHasKey('visibleFieldsTestLateArrival', $fd->getVisibleFieldSources(), 'the cached source map must be invalidated by the delete');
        $this->assertNull($fd->getVisibleFieldData($asset)['visibleFieldsTestLateArrival'], 'a deleted definition must no longer resolve');
    }

    public function testTheSourceMapIsBuiltOncePerConfigurationNotPerElement(): void
    {
        $assets = [
            $this->createAssetWithMetadata('one'),
            $this->createAssetWithMetadata('two'),
            $this->createAssetWithMetadata('three'),
        ];

        $fd = new class() extends ManyToManyRelation {
            public int $assetCandidateBuilds = 0;

            protected function getAssetVisibleFieldCandidates(): array
            {
                $this->assetCandidateBuilds++;

                return parent::getAssetVisibleFieldCandidates();
            }
        };
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'RelationTest']]);
        $fd->setAssetsAllowed(true)->setAssetTypes([]);
        $fd->setDocumentsAllowed(false);
        $fd->setVisibleFields(['filename', self::PREDEFINED_METADATA]);

        foreach ($assets as $index => $asset) {
            $data = $fd->getVisibleFieldData($asset);
            $this->assertSame($asset->getFilename(), $data['filename']);
            $this->assertSame(['one', 'two', 'three'][$index], $data[self::PREDEFINED_METADATA]);
        }
        $this->assertSame(1, $fd->assetCandidateBuilds, 'resolving several rows must not rebuild the asset candidates per row');

        // a configuration change is a different fingerprint and rebuilds the map once
        $fd->setAssetTypes([['assetTypes' => 'image']]);
        $fd->getVisibleFieldData($assets[0]);
        $fd->getVisibleFieldData($assets[1]);
        $this->assertSame(2, $fd->assetCandidateBuilds);

        // once assets are not allowed, the asset candidates are not consulted at all
        $fd->setAssetsAllowed(false);
        $this->assertNull($fd->getVisibleFieldData($assets[0])['filename']);
        $this->assertSame(2, $fd->assetCandidateBuilds);
    }

    public function testAdvancedRelationEditmodeRowsLeaveVisibleFieldValuesToTheConsumer(): void
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

        // the edit-mode rows are not extended: resolving values needs the related elements, which the
        // consumer fetches per row through getVisibleFieldData() when it renders the columns
        $rows = $fd->getDataForEditmode([$objectMetadata, $assetMetadata]);

        $this->assertCount(2, $rows);
        $this->assertSame($object->getId(), $rows[0]['id']);
        $this->assertSame('meta value', $rows[0]['filename'], 'metadata columns are still part of the rows');
        $this->assertArrayNotHasKey('someAttribute', $rows[0]);
        $this->assertArrayNotHasKey(self::PREDEFINED_METADATA, $rows[0]);
        $this->assertSame($asset->getId(), $rows[1]['id']);
        $this->assertArrayNotHasKey('someAttribute', $rows[1]);
        $this->assertArrayNotHasKey(self::PREDEFINED_METADATA, $rows[1]);

        // the values for those rows, resolved from the related elements
        $objectData = $fd->getVisibleFieldData($objectMetadata->getElement());
        $this->assertSame('object value', $objectData['someAttribute']);
        $this->assertNull($objectData['filename']);
        $this->assertNull($objectData[self::PREDEFINED_METADATA]);

        $assetData = $fd->getVisibleFieldData($assetMetadata->getElement());
        $this->assertNull($assetData['someAttribute']);
        $this->assertSame($asset->getFilename(), $assetData['filename']);
        $this->assertSame('(c) pimcore', $assetData[self::PREDEFINED_METADATA]);
    }

    public function testFieldsHoldingSecretsAreNeverExposed(): void
    {
        $object = TestHelper::createEmptyObject('visible-fields-');
        $object->setPassword('PasswordValue');
        $object->save();
        $this->assertNotEmpty($object->getPassword(), 'the password fixture must be stored (hashed)');

        $fd = new ManyToManyRelation();
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'unittest']]);
        $fd->setAssetsAllowed(false)->setDocumentsAllowed(false);

        $available = $fd->getAvailableVisibleFields();
        $this->assertArrayHasKey('input', $available, 'the class fields are offered');
        $this->assertArrayNotHasKey('password', $available);
        $this->assertArrayNotHasKey('encryptedField', $available);
        $this->assertArrayNotHasKey('password', $fd->getVisibleFieldSources());
        $this->assertArrayNotHasKey('encryptedField', $fd->getVisibleFieldSources());

        // even when configured (e.g. by hand in the class definition) the values are not resolved
        $fd->setVisibleFields(['password', 'encryptedField', 'input']);
        $fd->enrichLayoutDefinition(null);
        $this->assertSame([], $fd->visibleFieldDefinitions['password']['sources']);
        $this->assertSame([], $fd->visibleFieldDefinitions['encryptedField']['sources']);

        $data = $fd->getVisibleFieldData($object);
        $this->assertNull($data['password']);
        $this->assertNull($data['encryptedField']);
        $this->assertNotContains($object->getPassword(), $data);
    }

    public function testAdditionalAssetFieldsOfASubclassAreOfferedAndResolved(): void
    {
        $asset = TestHelper::createImageAsset('visible-fields-');

        $fd = new class() extends ManyToManyRelation {
            protected function getAssetVisibleFieldCandidates(): array
            {
                $candidates = parent::getAssetVisibleFieldCandidates();
                $candidates['fullPath'] = $this->buildVisibleFieldCandidate('fullPath', 'input', 'Full path');

                return $candidates;
            }

            protected function resolveAssetVisibleFieldValue(Asset $asset, string $name, array $params = []): mixed
            {
                if ($name === 'fullPath') {
                    return $asset->getRealFullPath();
                }

                return parent::resolveAssetVisibleFieldValue($asset, $name, $params);
            }
        };
        $fd->setObjectsAllowed(true)->setClasses([['classes' => 'RelationTest']]);
        $fd->setAssetsAllowed(true)->setAssetTypes([]);
        $fd->setDocumentsAllowed(false);
        $fd->setVisibleFields(['fullPath', 'filename']);

        $available = $fd->getAvailableVisibleFields();
        $this->assertSame(['asset'], $available['fullPath']['sources']);
        $this->assertSame('Full path', $available['fullPath']['title']);
        $this->assertSame(['asset'], $fd->getVisibleFieldSources()['fullPath'], 'the source map must follow the overridden candidates');

        $fd->enrichLayoutDefinition(null);
        $this->assertSame(['asset'], $fd->visibleFieldDefinitions['fullPath']['sources']);

        $data = $fd->getVisibleFieldData($asset);
        $this->assertSame($asset->getRealFullPath(), $data['fullPath']);
        $this->assertSame($asset->getFilename(), $data['filename'], 'the inherited fields keep resolving');

        $object = $this->createRelationTestObject('object value');
        $this->assertNull($fd->getVisibleFieldData($object)['fullPath'], 'an asset-only field must not resolve for objects');
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
