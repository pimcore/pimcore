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

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
class DateRangeTest extends TestCase
{
    private const START = 1700000000;

    private const END = 1700100000;

    private function buildField(): DateRange
    {
        $field = new DateRange();
        $field->setName('range');

        return $field;
    }

    public function testGetDataFromResourceWithBothDates(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__start_date' => self::START,
            'range__end_date' => self::END,
        ]);

        $this->assertInstanceOf(CarbonPeriod::class, $result);
        $this->assertSame(self::START, $result->getStartDate()->getTimestamp());
        $this->assertSame(self::END, $result->getEndDate()->getTimestamp());
    }

    /**
     * Regression test for #18144: an open-ended range (start set, end null) must not be dropped.
     */
    public function testGetDataFromResourceWithStartDateOnly(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__start_date' => self::START,
            'range__end_date' => null,
        ]);

        $this->assertInstanceOf(CarbonPeriod::class, $result);
        $this->assertSame(self::START, $result->getStartDate()->getTimestamp());
        $this->assertNull($result->getEndDate());
    }

    public function testGetDataFromResourceReturnsNullWhenStartDateMissing(): void
    {
        $result = $this->buildField()->getDataFromResource([
            'range__start_date' => null,
            'range__end_date' => null,
        ]);

        $this->assertNull($result);
    }

    public function testGetDataFromResourceReturnsNullWhenColumnsMissing(): void
    {
        $this->assertNull($this->buildField()->getDataFromResource([]));
    }

    /**
     * Full persistence round-trip for an open-ended range as performed on save/load.
     */
    public function testResourceRoundTripForOpenEndedRange(): void
    {
        $field = $this->buildField();

        $period = CarbonPeriod::create()->setStartDate(Carbon::createFromTimestamp(self::START));

        $resource = $field->getDataForResource($period);
        $this->assertSame(self::START, $resource['range__start_date']);
        $this->assertNull($resource['range__end_date']);

        $result = $field->getDataFromResource($resource);
        $this->assertInstanceOf(CarbonPeriod::class, $result);
        $this->assertSame(self::START, $result->getStartDate()->getTimestamp());
        $this->assertNull($result->getEndDate());
    }
}
