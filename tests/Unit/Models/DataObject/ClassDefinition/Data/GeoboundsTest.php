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

use Pimcore\Model\DataObject\ClassDefinition\Data\Geobounds;
use Pimcore\Model\DataObject\Data\Geobounds as GeoboundsValue;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
class GeoboundsTest extends TestCase
{
    private function buildField(): Geobounds
    {
        $field = new Geobounds();
        $field->setName('bounds');

        return $field;
    }

    public function testGetDataFromResourceWithCoordinates(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'bounds__NElongitude' => 13.0,
            'bounds__NElatitude' => 52.0,
            'bounds__SWlongitude' => 12.0,
            'bounds__SWlatitude' => 51.0,
        ]);

        $this->assertInstanceOf(GeoboundsValue::class, $result);
        $this->assertSame(13.0, $result->getNorthEast()->getLongitude());
        $this->assertSame(51.0, $result->getSouthWest()->getLatitude());
    }

    /**
     * Regression test for #18144: a coordinate of 0 must not drop the whole bounds value.
     */
    public function testGetDataFromResourceKeepsZeroCoordinates(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'bounds__NElongitude' => 0.0,
            'bounds__NElatitude' => 0.0,
            'bounds__SWlongitude' => 0.0,
            'bounds__SWlatitude' => 0.0,
        ]);

        $this->assertInstanceOf(GeoboundsValue::class, $result);
        $this->assertSame(0.0, $result->getNorthEast()->getLongitude());
        $this->assertSame(0.0, $result->getNorthEast()->getLatitude());
        $this->assertSame(0.0, $result->getSouthWest()->getLongitude());
        $this->assertSame(0.0, $result->getSouthWest()->getLatitude());
    }

    public function testGetDataFromResourceReturnsNullWhenCoordinatesNull(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'bounds__NElongitude' => null,
            'bounds__NElatitude' => null,
            'bounds__SWlongitude' => null,
            'bounds__SWlatitude' => null,
        ]);

        $this->assertNull($result);
    }

    public function testResourceRoundTripKeepsZeroCoordinates(): void
    {
        $field = $this->buildField();
        $value = new GeoboundsValue(
            new GeoCoordinates(0.0, 0.0),
            new GeoCoordinates(0.0, 0.0)
        );

        $resource = $field->getDataForResource($value);
        $result = $field->getDataFromResource($resource);

        $this->assertInstanceOf(GeoboundsValue::class, $result);
        $this->assertSame(0.0, $result->getNorthEast()->getLatitude());
        $this->assertSame(0.0, $result->getSouthWest()->getLongitude());
    }
}
