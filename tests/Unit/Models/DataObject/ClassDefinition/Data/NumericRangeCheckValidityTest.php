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
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * A boundary of a range may be absent. PHP compares null against a number as a boolean, so an
 * unguarded comparison rejects valid data: "7 > null" is true and made a range that only carries
 * a minimum fail validation.
 */
class NumericRangeCheckValidityTest extends TestCase
{
    private function field(): NumericRangeDefinition
    {
        $field = new NumericRangeDefinition();
        $field->setName('range');

        return $field;
    }

    public function testRangeWithOnlyAMinimumIsValid(): void
    {
        $this->expectNotToPerformAssertions();

        $this->field()->checkValidity(new NumericRange(7, null));
    }

    public function testRangeWithOnlyAMaximumIsValid(): void
    {
        $this->expectNotToPerformAssertions();

        $this->field()->checkValidity(new NumericRange(null, 7));
    }

    public function testMinimumBiggerThanMaximumIsStillRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->field()->checkValidity(new NumericRange(9, 7));
    }

    public function testAbsentBoundaryIsNotCheckedAgainstTheMinimumThreshold(): void
    {
        $this->expectNotToPerformAssertions();

        $field = $this->field();
        $field->setMinValue(5.0);

        // the absent minimum must not be read as 0 and reported as "not at least 5"
        $field->checkValidity(new NumericRange(null, 10));
    }

    public function testPresentBoundaryIsStillCheckedAgainstTheMinimumThreshold(): void
    {
        $this->expectException(ValidationException::class);

        $field = $this->field();
        $field->setMinValue(5.0);

        $field->checkValidity(new NumericRange(1, 10));
    }

    public function testPresentBoundaryIsStillCheckedAgainstTheMaximumThreshold(): void
    {
        $this->expectException(ValidationException::class);

        $field = $this->field();
        $field->setMaxValue(5.0);

        $field->checkValidity(new NumericRange(1, 10));
    }

    public function testUnsignedIsStillEnforcedForANegativeMinimum(): void
    {
        $this->expectException(ValidationException::class);

        $field = $this->field();
        $field->setUnsigned(true);

        $field->checkValidity(new NumericRange(-1, 10));
    }

    public function testUnsignedIsNotTrippedByAnAbsentMinimum(): void
    {
        $this->expectNotToPerformAssertions();

        $field = $this->field();
        $field->setUnsigned(true);

        $field->checkValidity(new NumericRange(null, 10));
    }
}
