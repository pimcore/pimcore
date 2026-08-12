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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Probe class used to detect whether __wakeup is invoked during a restricted unserialize.
 * It must NOT be in the allowlist (here: none at all) passed to getDataFromResource.
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
 * @group unit.model.datatype.imagegallery
 */
class ImageGalleryTest extends TestCase
{
    /**
     * Regression test for GHSA-8v44-m977-ph86.
     *
     * ImageGallery::getDataFromResource() unserialized the `__hotspots` column with
     * allowed_classes=true, even though the column only ever legitimately holds a serialized
     * array of strings (Hotspotimage::getDataForResource stringifies each entry before it is
     * collected). A stored payload whose top-level bytes decode to an object was therefore
     * instantiated - and any object with a dangerous __wakeup/__destruct became a PHP Object
     * Injection / RCE gadget. The unserialize runs before the `$images` guard, so a crafted
     * `__hotspots` value alone is enough to trigger it.
     */
    public function testGetDataFromResourceDoesNotInstantiateObjectsFromHotspots(): void
    {
        ImageGalleryObjectInjectionProbe::$wakeupCalled = false;

        $payload = serialize(new ImageGalleryObjectInjectionProbe());

        (new ImageGallery())->getDataFromResource([
            '__images' => null,
            '__hotspots' => $payload,
        ]);

        $this->assertFalse(
            ImageGalleryObjectInjectionProbe::$wakeupCalled,
            '__wakeup() must not be called when deserializing the __hotspots column - it must never instantiate objects'
        );
    }
}
