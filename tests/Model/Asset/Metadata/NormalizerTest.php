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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\Asset\Metadata;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Asset\Metadata\Loader\DataLoader;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class NormalizerTest
 *
 * @group model.asset.metadata.normalizer
 */
class NormalizerTest extends ModelTestCase
{
    /** @var Image */
    protected Image $testAsset;

    /** @var DataLoader */
    protected DataLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }

        $this->testAsset = TestHelper::createImageAsset();
        $this->loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function doCompare(int $assetId, string $metaDataName, $originalData)
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

    public function testAssetMetadata()
    {
        $metadataAsset = TestHelper::createImageAsset('metadata-');
        $metaDataName = 'asset-metadata';

        $this->testAsset->addMetadata($metaDataName, 'asset', $metadataAsset);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataAsset);
    }

    public function testDocumentMetadata()
    {
        $metadataDocument = TestHelper::createEmptyDocumentPage('metadata-');
        $metaDataName = 'document-metadata';

        $this->testAsset->addMetadata($metaDataName, 'document', $metadataDocument);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataDocument);
    }

    public function testDataObjectMetadata()
    {
        $metadataObject = TestHelper::createEmptyObject('metadata-');
        $metaDataName = 'object-metadata';

        $this->testAsset->addMetadata($metaDataName, 'object', $metadataObject);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $metadataObject);
    }

    public function testInputMetadata()
    {
        $originalData = 'foo bar';
        $metaDataName = 'input-metadata';
        $this->testAsset->addMetadata($metaDataName, 'input', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testTextAreaMetadata()
    {
        $originalData = "foo bar\nsecond line";
        $metaDataName = 'textarea-metadata';
        $this->testAsset->addMetadata($metaDataName, 'textarea', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testDateMetadata()
    {
        $originalData = time();
        $metaDataName = 'date-metadata';
        $this->testAsset->addMetadata($metaDataName, 'date', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testCheckboxMetadata()
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

    public function testSelectMetadata()
    {
        $originalData = 'somevalue';
        $metaDataName = 'select-metadata';
        $this->testAsset->addMetadata($metaDataName, 'select', $originalData);
        $this->testAsset->save();

        $this->doCompare($this->testAsset->getId(), $metaDataName, $originalData);
    }

    public function testLocalizedMetaData() {
        $metaDataName = 'localized-metadata';
        $originalData = 'some-value';
        $localizedData = 'some-localized-value';
        $language = 'en';

        $notLocalizedMetadataName = 'not-localized-metadata';
        $notLocalizedMetadata = 'different-not-localized-value';

        $this->testAsset->addMetadata($metaDataName, 'input', $originalData);
        $this->testAsset->addMetadata($metaDataName, 'input', $localizedData, $language);
        $this->testAsset->addMetadata($notLocalizedMetadataName, 'input', $notLocalizedMetadata);
        $this->testAsset->save();

        $asset = Asset::getById($this->testAsset->getId());

        // When language not specified and strict match is disabled, metadata without locale should be returned
        $savedMetaData = $asset->getMetadata($metaDataName, null, false, true);
        $this->assertEquals($originalData, $savedMetaData['data']);

        // When only name is given, localized value should be returned if found, else value with no locale shall be returned
        $savedMetaData = $asset->getMetadata($metaDataName);
        $this->assertEquals($savedMetaData, $localizedData);

        $savedMetaData = $asset->getMetadata($notLocalizedMetadataName);
        $this->assertEquals($savedMetaData, $notLocalizedMetadata);

        // When strict locale is passed to metadata without locale, result must be null
        $savedMetaData = $asset->getMetadata($notLocalizedMetadataName, 'en', true, true);
        $this->assertEquals($savedMetaData, null);

        // When name is given and language is strictly matched, the metadata should contain specified value and language
        $savedMetaData = $asset->getMetadata($metaDataName, $language, true, true);
        $this->assertEquals($savedMetaData['language'], $language);
        $this->assertEquals($savedMetaData['data'], $localizedData);

        // When name is not given and language is strictly matched, only metadata with specified locale should be returned
        $savedMetaData = $asset->getMetadata(null, $language, true, true);
        $this->assertCount(1, $savedMetaData);

        // When no name given and language is not strictly matched, all metadata should be returned
        $savedMetaData = $asset->getMetadata(null, $language, false, true);
        $this->assertCount(3, $savedMetaData);

        // When no parameters are passed, metadata with locale should get priority over the ones that don't
        $savedMetaData = $asset->getMetadata();
        $this->assertCount(3, $savedMetaData);

    }
}
