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

    /**
     * @inheritdoc
     */
    protected function needsDb()
    {
        return true;
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testAssetMetadata()
    {
        $metadataAsset = TestHelper::createImageAsset('metadata-');
        $this->testAsset->addMetadata('asset-metadata', 'asset', $metadataAsset);

        /** @var Data $instance */
        $instance = $this->loader->build('asset');

        //normalize => denormalize and then check denormalized data should same as original
        $normalizedData = $instance->normalize($this->testAsset->getMetadata('asset-metadata'));
        $denormalizedData = $instance->denormalize($normalizedData);

        $this->assertEquals($metadataAsset, $denormalizedData);
    }

    public function testDocumentMetadata()
    {
        $metadataDocument = TestHelper::createEmptyDocumentPage('metadata-');
        $this->testAsset->addMetadata('document-metadata', 'document', $metadataDocument);

        /** @var Data $instance */
        $instance = $this->loader->build('document');

        //normalize => denormalize and then check denormalized data should same as original
        $normalizedData = $instance->normalize($this->testAsset->getMetadata('document-metadata'));
        $denormalizedData = $instance->denormalize($normalizedData);

        $this->assertEquals($metadataDocument, $denormalizedData);
    }

    public function testDataObjectMetadata()
    {
        $metadataObject = TestHelper::createEmptyObject('metadata-');
        $this->testAsset->addMetadata('object-metadata', 'object', $metadataObject);

        /** @var Data $instance */
        $instance = $this->loader->build('object');

        //normalize => denormalize and then check denormalized data should same as original
        $normalizedData = $instance->normalize($this->testAsset->getMetadata('object-metadata'));
        $denormalizedData = $instance->denormalize($normalizedData);

        $this->assertEquals($metadataObject, $denormalizedData);
    }
}
