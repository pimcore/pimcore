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

use Pimcore\Model\DataObject\ClassDefinition\Data\NumericRange;
use Pimcore\Model\DataObject\Data\NumericRange as NumericRangeValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
class NumericRangeTest extends TestCase
{
    private function buildField(): NumericRange
    {
        $field = new NumericRange();
        $field->setName('range');

        return $field;
    }

    public function testGetDataFromResourceWithBothBounds(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => 5.0,
            'range__maximum' => 10.0,
        ]);

        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertSame(5.0, $result->getMinimum());
        $this->assertSame(10.0, $result->getMaximum());
    }

    /**
     * Regression test for #18144: a range with only the minimum set must not be dropped.
     */
    public function testGetDataFromResourceWithMinimumOnly(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => 5.0,
            'range__maximum' => null,
        ]);

        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertSame(5.0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    /**
     * Regression test for #18144: a range with only the maximum set must not be dropped.
     */
    public function testGetDataFromResourceWithMaximumOnly(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => null,
            'range__maximum' => 10.0,
        ]);

        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertNull($result->getMinimum());
        $this->assertSame(10.0, $result->getMaximum());
    }

    public function testGetDataFromResourceKeepsZeroBound(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => 0.0,
            'range__maximum' => null,
        ]);

        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertSame(0.0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    public function testGetDataFromResourceCastsStringBounds(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => '5',
            'range__maximum' => null,
        ]);

        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertSame(5.0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    public function testGetDataFromResourceReturnsNullWhenBothBoundsNull(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__minimum' => null,
            'range__maximum' => null,
        ]);

        $this->assertNull($result);
    }

    public function testGetDataFromResourceReturnsNullWhenColumnsMissing(): void
    {
        $this->assertNull($this->buildField()->getDataFromResource([]));
    }

    /**
     * Full persistence round-trip for a partial range as performed on save/load.
     */
    public function testResourceRoundTripForPartialRange(): void
    {
        $field = $this->buildField();

        $resource = $field->getDataForResource(new NumericRangeValue(5.0, null));
        $this->assertSame(['range__minimum' => 5.0, 'range__maximum' => null], $resource);

        $result = $field->getDataFromResource($resource);
        $this->assertInstanceOf(NumericRangeValue::class, $result);
        $this->assertSame(5.0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }
}
