<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * GNU Affero General Public License version 3 (AGPLv3).
 */

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\ImageGallery as ImageGalleryValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Probe class used to detect whether __wakeup is invoked during a restricted unserialize.
 */
class ImageGalleryObjectInjectionProbe
{
    public static bool $wakeupCalled = false;

    public function __wakeup(): void
    {
        self::$wakeupCalled = true;
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

/**
 * Regression tests for ImageGallery deserialization security.
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
        ImageGalleryObjectInjectionProbe::$wakeupCalled = false;

        // A legitimate `__hotspots` column only ever contains an array of already-serialized
        // strings. This payload instead places a real object directly in the outer array,
        // simulating a poisoned database value.
        $payload = serialize([
            new ImageGalleryDeserializeCanary(),
            new ImageGalleryObjectInjectionProbe(),
        ]);

        $this->buildField()->getDataFromResource([
            'gallery__images' => '',
            'gallery__hotspots' => $payload,
        ]);

        $this->assertFalse(
            ImageGalleryDeserializeCanary::$fired,
            'Unserializing the __hotspots column must not instantiate arbitrary classes.'
        );

        $this->assertFalse(
            ImageGalleryObjectInjectionProbe::$wakeupCalled,
            '__wakeup() must not be called for arbitrary objects embedded in the hotspots payload.'
        );
    }

    public function testGetDataFromResourceStillAcceptsLegitimateHotspotStrings(): void
    {
        // Legitimate shape produced by getDataForResource(): an array of per-item serialized
        // strings. Unserializing this shape must keep working after restricting allowed_classes.
        $payload = serialize([
            'a:1:{s:1:"x";s:1:"y";}',
            'b:0;',
        ]);

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