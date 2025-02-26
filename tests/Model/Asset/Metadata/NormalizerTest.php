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

namespace Pimcore\Tests\Model\Asset\Metadata;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Asset\Metadata\Loader\DataLoader;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class NormalizerTest
 *
 * @group model.asset.metadata.normalizer
 */
class NormalizerTest extends ModelTestCase
{
    protected Image $testAsset;

    protected DataLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }

        $this->testAsset = TestHelper::createImageAsset();
        $this->loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function doCompare(int $assetId, string $metaDataName, mixed $originalData): void
    {
        $asset = Asset::getById($assetId, ['force' => true]);
        $metaDataArray = $asset->getMetadata($metaDataName, null, false, true);

        /** @var Data $instance */
        $instance = $this->loader->build($metaDataArray['type']);

        $metaData = $asset->getMetadata($metaDataName);

        //normalize => denormalize and then check denormalized data should same as original
        $normalizedData = $instance->normalize($metaData);
        $denormalizedData = $instance->denormalize($normalizedData);

        $this->assertEquals($originalData, $denormalizedData);
    }

    protected function compareRawMetaData(array $metaData, string $expectedName, string|int|float|null $expectedData, ?string $expectedLanguage, string $expectedType): void
    {
        $this->assertIsArray($metaData);
        $this->assertArrayHasKey('name', $metaData);
        $this->assertArrayHasKey('data', $metaData);
        $this->assertArrayHasKey('type', $metaData);
        $this->assertArrayHasKey('language', $metaData);
        $this->assertEquals($metaData['name'], $expectedName);
        $this->assertEquals($metaData['data'], $expectedData);
        $this->assertEquals($metaData['type'], $expectedType);
        $this->assertEquals($metaData['language'], $expectedLanguage);
    }

    public function testLocalizedMetaData(): void
    {
        $languages = [
            'en', 'de', null,
        ];

        $metaDataNames = [
            'metadata-one',
            'metadata-two',
            'metadata-three',
        ];

        foreach ($languages as $lang) {
            foreach ($metaDataNames as $name) {
                $this->testAsset->addMetadata($name, 'input', $lang ? $name . '-' . $lang : $name, $lang);
            }
        }

        $this->testAsset->addMetadata('metadata-four', 'input', 'metadata-four');

        $this->testAsset->save();

        $asset = Asset::getById($this->testAsset->getId());

        // passing  no parameters should return all available metadata
        $metaData = $asset->getMetadata();
        $this->assertCount(10, $metaData);

        // passing only name should return metadata with default language if there exists one
        $metaData = $asset->getMetadata('metadata-one');
        $this->assertEquals('metadata-one-en', $metaData);

        // passing only name should return metadata with no language if there exists none with default language
        $metaData = $asset->getMetadata('metadata-four');
        $this->assertEquals('metadata-four', $metaData);

        // passing name and language should return metadata with given language if there exists one
        $metaData = $asset->getMetadata('metadata-one', 'de');
        $this->assertEquals('metadata-one-de', $metaData);

        // passing name and language should return the default value if metadata value for the requested locale is not found
        $metaData = $asset->getMetadata('metadata-four', 'de');
        $this->assertEquals('metadata-four', $metaData);

        // passing name, language and strictMatch should return exact value of locale if found, else should return null
        $metaData = $asset->getMetadata('metadata-four', 'fr', true);
        $this->assertEquals(null, $metaData);

        // passing raw = true should return value with other information
        // passing name and language should return the default value if metadata value for the requested locale is not found
        $metaData = $asset->getMetadata('metadata-four', 'de', false, true);
        $this->compareRawMetaData($metaData, 'metadata-four', 'metadata-four', null, 'input');

        // even if raw is enabled, strict matching should produce null if the translation is not available for requested locale
        $metaData = $asset->getMetadata('metadata-four', 'de', true, true);
        $this->assertEquals(null, $metaData);

        // when no name is passed but language is set and strictMatch and raw are true, array containing raw metadata for the given language should be returned
        $metaData = $asset->getMetadata(null, 'en', true, true);
        $this->assertIsArray($metaData);
        $this->assertCount(3, $metaData);
        foreach ($metaData as $md) {
            $this->assertArrayHasKey('language', $md);
            $this->assertEquals($md['language'], 'en');
        }

        // when no name is passed but language is set, strictMatch is false and raw is true, array containing raw metadata for the given language along with the ones which do not have language set should be returned
        $metaData = $asset->getMetadata(null, 'de', false, true);
        $this->assertIsArray($metaData);
        $this->assertCount(4, $metaData);
        foreach ($metaData as $md) {
            $this->assertArrayHasKey('language', $md);
            $this->assertTrue(in_array($md['language'], ['de', null]));
        }
    }

    public function testAssetMetadata(): void
    {
        $metadataAsset = TestHelper::createImageAsset('metadata-');
        $metaDataName = 'asset-metadata';

        $this->testAsset->addMetadata($metaDataName, 'asset', $metadataAsset);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataAsset);
    }

    public function testDocumentMetadata(): void
    {
        $metadataDocument = TestHelper::createEmptyDocumentPage('metadata-');
        $metaDataName = 'document-metadata';

        $this->testAsset->addMetadata($metaDataName, 'document', $metadataDocument);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataDocument);
    }

    public function testDataObjectMetadata(): void
    {
        $metadataObject = TestHelper::createEmptyObject('metadata-');
        $metaDataName = 'object-metadata';

        $this->testAsset->addMetadata($metaDataName, 'object', $metadataObject);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataObject);
    }

    public function testInputMetadata(): void
    {
        $originalData = 'foo bar';
        $metaDataName = 'input-metadata';
        $this->testAsset->addMetadata($metaDataName, 'input', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testTextAreaMetadata(): void
    {
        $originalData = "foo bar\nsecond line";
        $metaDataName = 'textarea-metadata';
        $this->testAsset->addMetadata($metaDataName, 'textarea', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testDateMetadata(): void
    {
        $originalData = time();
        $metaDataName = 'date-metadata';
        $this->testAsset->addMetadata($metaDataName, 'date', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testCheckboxMetadata(): void
    {
        $originalData = true;
        $metaDataName = 'checkbox-metadata';
        $this->testAsset->addMetadata($metaDataName, 'checkbox', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);

        $originalData = false;
        $metaDataName = 'checkbox-metadata';
        $this->testAsset->addMetadata($metaDataName, 'checkbox', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testSelectMetadata(): void
    {
        $originalData = 'somevalue';
        $metaDataName = 'select-metadata';
        $this->testAsset->addMetadata($metaDataName, 'select', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }
}
