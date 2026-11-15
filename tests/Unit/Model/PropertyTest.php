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

namespace Pimcore\Tests\Unit\Model;

use Carbon\Carbon;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-xw5x-q46c-wh4j: a date-type property reconstructed its stored data
 * with unserialize($data, ['allowed_classes' => true]), and the editmode write path stored
 * whatever string a backend user submitted verbatim (no parsing/validation), so any user with
 * element-edit permission could plant an arbitrary serialized object that got instantiated on
 * every subsequent load of the element.
 */
class PropertyTest extends TestCase
{
    public function testSetDataFromResourceDoesNotInstantiateADisallowedClass(): void
    {
        $payload = serialize(new PropertyDeserializeCanary());
        PropertyDeserializeCanary::$fired = false;

        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource($payload);

        $this->assertFalse(PropertyDeserializeCanary::$fired);
        $this->assertNull($property->getData());
    }

    public function testSetDataFromResourceNormalizesMalformedAllowlistedClassToNull(): void
    {
        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource('O:8:"DateTime":0:{}');

        $this->assertNull($property->getData());
    }

    public function testSetDataFromResourceKeepsEmptyValuesAsIs(): void
    {
        $property = new Property();
        $property->setType('date');

        $property->setDataFromResource('');
        $this->assertSame('', $property->getData());

        $property->setDataFromResource(null);
        $this->assertNull($property->getData());
    }

    public function testSetDataFromResourceNormalizesNonDateValueToNull(): void
    {
        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource(serialize('not a date'));

        $this->assertNull($property->getData());
    }

    public function testSetDataFromResourceStillReconstructsALegitimateDate(): void
    {
        $date = new Carbon('2024-05-06 07:08:09');

        $property = new Property();
        $property->setType('date');
        $property->setDataFromResource(serialize($date));

        $result = $property->getData();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame($date->getTimestamp(), $result->getTimestamp());
    }

    public function testSetDataFromEditmodeNeverStoresARawSerializedString(): void
    {
        $payload = serialize(new PropertyDeserializeCanary());

        $property = new Property();
        $property->setType('date');
        $property->setDataFromEditmode($payload);

        $this->assertNull($property->getData());
    }

    public function testSetDataFromEditmodeStillAcceptsALegitimateDateString(): void
    {
        $property = new Property();
        $property->setType('date');
        $property->setDataFromEditmode('2024-05-06 07:08:09');

        $result = $property->getData();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame(strtotime('2024-05-06 07:08:09'), $result->getTimestamp());
    }

    public function testSetDataFromEditmodeStillAcceptsATimestamp(): void
    {
        $property = new Property();
        $property->setType('date');
        $property->setDataFromEditmode('1715000000');

        $result = $property->getData();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame(1715000000, $result->getTimestamp());
    }
}

/**
 * Canary whose magic methods flip a static flag, so a test can detect whether it was
 * instantiated (or had its magic methods run) during deserialization.
 */
class PropertyDeserializeCanary
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
