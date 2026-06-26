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

use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
class GeopointTest extends TestCase
{
    private function buildField(): Geopoint
    {
        $field = new Geopoint();
        $field->setName('point');

        return $field;
    }

    public function testGetDataFromResourceWithCoordinates(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'point__longitude' => 13.0,
            'point__latitude' => 52.0,
        ]);

        $this->assertInstanceOf(GeoCoordinates::class, $result);
        $this->assertSame(13.0, $result->getLongitude());
        $this->assertSame(52.0, $result->getLatitude());
    }

    /**
     * Regression test for #18144: a coordinate of 0 (prime meridian / equator) must not be dropped.
     */
    public function testGetDataFromResourceKeepsZeroLongitude(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'point__longitude' => 0.0,
            'point__latitude' => 52.0,
        ]);

        $this->assertInstanceOf(GeoCoordinates::class, $result);
        $this->assertSame(0.0, $result->getLongitude());
        $this->assertSame(52.0, $result->getLatitude());
    }

    public function testGetDataFromResourceKeepsNullIsland(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'point__longitude' => 0.0,
            'point__latitude' => 0.0,
        ]);

        $this->assertInstanceOf(GeoCoordinates::class, $result);
        $this->assertSame(0.0, $result->getLongitude());
        $this->assertSame(0.0, $result->getLatitude());
    }

    public function testGetDataFromResourceReturnsNullWhenCoordinatesNull(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'point__longitude' => null,
            'point__latitude' => null,
        ]);

        $this->assertNull($result);
    }

    public function testResourceRoundTripKeepsZeroCoordinates(): void
    {
        $field = $this->buildField();

        $resource = $field->getDataForResource(new GeoCoordinates(0.0, 0.0));
        $this->assertSame(['point__longitude' => 0.0, 'point__latitude' => 0.0], $resource);

        $result = $field->getDataFromResource($resource);
        $this->assertInstanceOf(GeoCoordinates::class, $result);
        $this->assertSame(0.0, $result->getLongitude());
        $this->assertSame(0.0, $result->getLatitude());
    }
}
