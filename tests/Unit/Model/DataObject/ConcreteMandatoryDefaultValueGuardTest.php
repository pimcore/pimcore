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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for PEES-1279.
 *
 * Concrete::update() decides whether to skip the mandatory check on object
 * creation for a field that has a configured default value. That guard used
 * to read `empty($value) && !empty($fd->getDefaultValue())`, which mishandles
 * a value/default of 0 or false (empty(0) and empty(false) are both true in
 * PHP). The fix replaces those checks with `$fd->isEmpty($value)` and
 * `$fd->getDefaultValue() !== null`.
 *
 * This test exercises those two building blocks directly against real field
 * definitions, since Concrete::update() itself can only be exercised through
 * a full save/publish integration test (see
 * ObjectTest::testDefaultValueAndMandatorySavedToVersion() for the existing
 * precedent with a non-falsy default).
 */
class ConcreteMandatoryDefaultValueGuardTest extends TestCase
{
    public function testNumericFieldWithZeroDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Numeric();
        $field->setName('mandatoryNumericWithZeroDefault');
        $field->setMandatory(true);
        $field->setDefaultValue(0);

        // the value getter would return on a brand-new object where the setter was never called
        $unsetValue = null;

        $this->assertTrue($field->isEmpty($unsetValue), 'An unset value must still be considered empty');
        $this->assertNotNull($field->getDefaultValue(), 'A default of 0 must be recognized as "has a default"');

        // the exact guard from Concrete::update() after the fix
        $this->assertTrue(
            $field->isEmpty($unsetValue) && $field->getDefaultValue() !== null,
            'A mandatory numeric field with a default of 0 must get the create-time mandatory-check bypass'
        );

        // the pre-fix guard, kept here to document the regression this replaces
        $this->assertFalse(
            empty($unsetValue) && !empty($field->getDefaultValue()),
            'Sanity check: the old empty()-based guard failed to recognize a default of 0'
        );
    }

    public function testNumericFieldValueOfZeroIsNotTreatedAsEmpty(): void
    {
        $field = new Numeric();
        $field->setName('numericField');

        $this->assertFalse($field->isEmpty(0), 'A numeric value of 0 must not be treated as empty');
        $this->assertTrue(empty(0), 'Sanity check: PHP\'s empty() treats 0 as empty, which is the bug this fix avoids');
    }

    public function testCheckboxFieldWithFalseDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Checkbox();
        $field->setName('mandatoryCheckboxWithFalseDefault');
        $field->setMandatory(true);
        $field->setDefaultValue(0);

        $unsetValue = null;

        $this->assertTrue($field->isEmpty($unsetValue), 'An unset value must still be considered empty');
        $this->assertNotNull($field->getDefaultValue(), 'A default of false/0 must be recognized as "has a default"');

        $this->assertTrue(
            $field->isEmpty($unsetValue) && $field->getDefaultValue() !== null,
            'A mandatory checkbox with a default of false must get the create-time mandatory-check bypass'
        );

        $this->assertFalse(
            empty($unsetValue) && !empty($field->getDefaultValue()),
            'Sanity check: the old empty()-based guard failed to recognize a default of false/0'
        );
    }

    public function testCheckboxFieldValueOfFalseIsNotTreatedAsEmpty(): void
    {
        $field = new Checkbox();
        $field->setName('checkboxField');

        $this->assertFalse($field->isEmpty(false), 'A checkbox value of false must not be treated as empty');
        $this->assertTrue(empty(false), 'Sanity check: PHP\'s empty() treats false as empty, which is the bug this fix avoids');
    }
}
