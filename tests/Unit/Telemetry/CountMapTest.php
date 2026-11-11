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

use Pimcore\Telemetry\Snapshot\CountMap;
use Pimcore\Tests\Support\Test\TestCase;

class CountMapTest extends TestCase
{
    public function testRanksByCountThenByKey(): void
    {
        $this->assertSame(['a' => 3, 'c' => 3, 'b' => 1], (new CountMap())->ranked(['b' => 1, 'a' => 3, 'c' => 3]));
    }

    public function testTheTailBeyondTheLimitIsOneResidualFigure(): void
    {
        $ranked = (new CountMap())->ranked(['a' => 5, 'b' => 4, 'c' => 3, 'd' => 2, 'e' => 1], 3);

        $this->assertSame(['a' => 5, 'b' => 4, 'c' => 3, 'other' => 3], $ranked);
    }

    /**
     * A residual that already exists in the input never takes one of the named slots: the limit
     * applies to real names, the tail is added to the residual, and the final map is ranked as a whole.
     */
    public function testAnExistingResidualKeepsEveryNamedSlotAndTheMapStaysRanked(): void
    {
        $ranked = (new CountMap())->ranked(['other' => 50, 'a' => 10, 'b' => 9, 'c' => 8, 'd' => 7], 3);

        $this->assertSame(['other' => 57, 'a' => 10, 'b' => 9, 'c' => 8], $ranked);
    }

    public function testWithinTheLimitNothingIsFoldedAndTheResidualRanksLikeAnyKey(): void
    {
        $this->assertSame(['a' => 2, 'other' => 1], (new CountMap())->ranked(['other' => 1, 'a' => 2], 10));
    }

    /**
     * A residual that exists in the input stays visible even at zero, exactly like a named key at zero:
     * a configured queue with nothing waiting is a fact, not an absence.
     */
    public function testAZeroResidualStaysVisible(): void
    {
        $this->assertSame(['a' => 2, 'other' => 0], (new CountMap())->ranked(['other' => 0, 'a' => 2]));
    }

    public function testAnEmptyMapStaysEmpty(): void
    {
        $this->assertSame([], (new CountMap())->ranked([]));
    }
}
