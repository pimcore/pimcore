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

namespace Pimcore\Tests\Unit\Model;

use Carbon\Carbon;
use DateTime;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-2jfp-g3pc-m36g: Property::setDataFromResource() used to unserialize
 * a `date`-typed property's stored data with every class allowed, so any string stored in
 * `properties.data` (e.g. via the admin properties editor) was deserialized as an arbitrary
 * attacker-chosen class.
 *
 * @see Property::setDataFromResource()
 */
class PropertyTest extends TestCase
{
    public function testDateTypeDoesNotInstantiateDisallowedClasses(): void
    {
        PropertyTestCanary::$fired = false;
        $payload = serialize(new PropertyTestCanary());

        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource($payload);

        $this->assertFalse(PropertyTestCanary::$fired, 'An arbitrary class must not be instantiated when loading a date property.');
        $this->assertNotInstanceOf(PropertyTestCanary::class, $property->getData());
    }

    public function testDateTypeStillRestoresACarbonInstance(): void
    {
        $date = new Carbon('2026-01-15 10:00:00');
        $payload = serialize($date);

        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource($payload);

        $restored = $property->getData();
        $this->assertInstanceOf(Carbon::class, $restored);
        $this->assertSame($date->getTimestamp(), $restored->getTimestamp());
    }

    public function testDateTypeStillRestoresADateTimeInstance(): void
    {
        $date = new DateTime('2026-01-15 10:00:00');
        $payload = serialize($date);

        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource($payload);

        $restored = $property->getData();
        $this->assertInstanceOf(DateTime::class, $restored);
        $this->assertSame($date->getTimestamp(), $restored->getTimestamp());
    }
}

/**
 * Canary whose magic methods flip a static flag, so a test can detect whether it was
 * instantiated during deserialization.
 */
class PropertyTestCanary
{
    public static bool $fired = false;

    public function __wakeup(): void
    {
        self::$fired = true;
    }

    public function __destruct()
    {
        self::$fired = true;
    }
}
