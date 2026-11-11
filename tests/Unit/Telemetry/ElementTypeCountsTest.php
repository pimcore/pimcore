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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\Snapshot\ElementTypeCounts;
use Pimcore\Tests\Support\Test\TestCase;

class ElementTypeCountsTest extends TestCase
{
    public function testTotalSumsEveryTypeCount(): void
    {
        $counts = new ElementTypeCounts(['image' => 30, 'video' => 5, 'folder' => 7]);

        $this->assertSame(42, $counts->total());
    }

    public function testOfTypeReturnsTheCountOrZeroWhenAbsent(): void
    {
        $counts = new ElementTypeCounts(['image' => 30]);

        $this->assertSame(30, $counts->ofType('image'));
        $this->assertSame(0, $counts->ofType('video'));
    }

    public function testDistinctTypesCountsTheKeys(): void
    {
        $counts = new ElementTypeCounts(['image' => 30, 'video' => 5, 'audio' => 1]);

        $this->assertSame(3, $counts->distinctTypes());
    }

    public function testEmptyIsAllZero(): void
    {
        $counts = new ElementTypeCounts();

        $this->assertSame(0, $counts->total());
        $this->assertSame(0, $counts->ofType('image'));
        $this->assertSame(0, $counts->distinctTypes());
    }
}
