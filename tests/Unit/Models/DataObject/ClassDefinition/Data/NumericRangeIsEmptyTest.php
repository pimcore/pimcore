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

use Pimcore\Model\DataObject\ClassDefinition\Data\NumericRange as NumericRangeDefinition;
use Pimcore\Model\DataObject\Data\NumericRange;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Data::isEmpty() falls back to empty(), which is never true for an object, so inheritance
 * resolution and "hide empty data" would treat a range without boundaries as a value.
 */
class NumericRangeIsEmptyTest extends TestCase
{
    public function testRangeWithoutBoundariesIsEmpty(): void
    {
        $field = new NumericRangeDefinition();

        $this->assertTrue($field->isEmpty(new NumericRange(null, null)));
    }

    public function testHalfOpenRangeIsNotEmpty(): void
    {
        $field = new NumericRangeDefinition();

        $this->assertFalse($field->isEmpty(new NumericRange(null, 7)));
        $this->assertFalse($field->isEmpty(new NumericRange(7, null)));
    }

    public function testRangeOfZerosIsNotEmpty(): void
    {
        $field = new NumericRangeDefinition();

        $this->assertFalse($field->isEmpty(new NumericRange(0, 0)));
    }

    public function testNullIsEmpty(): void
    {
        $field = new NumericRangeDefinition();

        $this->assertTrue($field->isEmpty(null));
    }
}
