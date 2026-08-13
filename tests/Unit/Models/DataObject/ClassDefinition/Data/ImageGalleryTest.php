<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\ImageGallery as ImageGalleryValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-8h86-9g29-q362: ImageGallery::getDataFromResource() unserialized the
 * `__hotspots` resource column with allowed_classes true, letting a stored value that was not
 * produced through the normal write path instantiate arbitrary classes (POP-gadget chain).
 */
class ImageGalleryTest extends TestCase
{
    private function buildField(): ImageGallery
    {
        $field = new ImageGallery();
        $field->setName('gallery');

        return $field;
    }

    public function testGetDataFromResourceDoesNotInstantiateArbitraryClasses(): void
    {
        ImageGalleryDeserializeCanary::$fired = false;

        // A legitimate `__hotspots` column only ever contains an array of already-serialized
        // strings (see ImageGallery::getDataForResource()). This payload instead places a real
        // object directly in the outer array, simulating a value that reached the column via
        // some path other than the normal write flow.
        $payload = serialize([new ImageGalleryDeserializeCanary()]);

        $this->buildField()->getDataFromResource([
            'gallery__images' => '',
            'gallery__hotspots' => $payload,
        ]);

        $this->assertFalse(
            ImageGalleryDeserializeCanary::$fired,
            'Unserializing the __hotspots column must not instantiate arbitrary classes.'
        );
    }

    public function testGetDataFromResourceStillAcceptsLegitimateHotspotStrings(): void
    {
        // Legitimate shape produced by getDataForResource(): an array of per-item serialized
        // strings. Unserializing this shape must keep working (no exception) after restricting
        // allowed_classes.
        $payload = serialize(['a:1:{s:1:"x";s:1:"y";}', 'b:0;']);

        $result = $this->buildField()->getDataFromResource([
            'gallery__images' => '',
            'gallery__hotspots' => $payload,
        ]);

        $this->assertInstanceOf(ImageGalleryValue::class, $result);
    }

    public function testGetDataFromResourceReturnsEmptyGalleryWhenNoImages(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'gallery__images' => '',
            'gallery__hotspots' => null,
        ]);

        $this->assertInstanceOf(ImageGalleryValue::class, $result);
        $this->assertSame([], $result->getItems());
    }
}

/**
 * Canary whose magic method flips a static flag, so a test can detect whether it was
 * instantiated during deserialization.
 */
class ImageGalleryDeserializeCanary
{
    public static bool $fired = false;

    public function __wakeup(): void
    {
        self::$fired = true;
    }
}
