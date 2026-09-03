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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for PEES-1279.
 *
 * Concrete::update() decides whether to skip the mandatory check on object
 * creation for a field that has a configured default value. That guard used
 * to read `empty($value) && !empty($fd->getDefaultValue())`, which mishandles
 * a value/default of 0 or false (empty(0) and empty(false) are both true in
 * PHP). The fix replaces those checks with `$fd->isEmpty($value)` for the
 * value and `!$fd->isEmpty($fd->getDefaultValue())` for the default, using
 * each field's own type-aware emptiness rules rather than PHP's empty().
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
        $this->assertFalse($field->isEmpty($field->getDefaultValue()), 'A default of 0 must be recognized as "has a default"');

        // the exact guard from Concrete::update() after the fix
        $this->assertTrue(
            $field->isEmpty($unsetValue) && !$field->isEmpty($field->getDefaultValue()),
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
        $this->assertFalse($field->isEmpty($field->getDefaultValue()), 'A default of false/0 must be recognized as "has a default"');

        $this->assertTrue(
            $field->isEmpty($unsetValue) && !$field->isEmpty($field->getDefaultValue()),
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

    /**
     * Regression guard: a naive `$fd->getDefaultValue() !== null` check (an
     * earlier draft of this fix) would have treated a Select field's empty
     * string default as "has a default" and incorrectly bypassed the
     * mandatory check, potentially letting a mandatory field stay empty on
     * publish. The field's own isEmpty() must be used instead.
     */
    public function testSelectFieldWithEmptyStringDefaultIsNotRecognizedAsHavingADefault(): void
    {
        $field = new Select();
        $field->setName('mandatorySelectWithEmptyDefault');
        $field->setMandatory(true);
        $field->setDefaultValue('');

        $this->assertNotNull($field->getDefaultValue(), 'Sanity check: the stored default is an empty string, not null');
        $this->assertTrue($field->isEmpty($field->getDefaultValue()), 'An empty string default must be considered empty for a Select field');

        // the exact guard from Concrete::update() after the fix must NOT bypass here
        $this->assertFalse(
            !$field->isEmpty($field->getDefaultValue()),
            'A mandatory select field with a genuinely empty default must not get the create-time mandatory-check bypass'
        );
    }

    /**
     * QuantityValue::doGetDefaultValue() constructs a default from
     * `getDefaultValue() || getDefaultUnit()` — so a scalar default of 0
     * combined with a configured default unit still resolves to a real
     * default (value=0, unit=<configured>), even though the scalar value
     * alone reads as empty. The guard must account for the unit, not just
     * the scalar accessor.
     */
    public function testQuantityValueFieldWithZeroDefaultAndUnitIsRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithZeroDefaultAndUnit');
        $field->setMandatory(true);
        $field->setDefaultValue(0);
        $field->setDefaultUnit('unit-1');

        $this->assertTrue(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory quantity value field with a scalar default of 0 but a configured default unit must get the create-time mandatory-check bypass'
        );
    }

    public function testQuantityValueFieldWithNoDefaultIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithNoDefault');
        $field->setMandatory(true);

        $this->assertFalse(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory quantity value field with neither a default value nor a default unit must not get the bypass'
        );
    }

    /**
     * Same compound value+unit default shape as QuantityValue.
     */
    public function testInputQuantityValueFieldWithZeroDefaultAndUnitIsRecognizedAsHavingADefault(): void
    {
        $field = new InputQuantityValue();
        $field->setName('mandatoryInputQuantityValueWithZeroDefaultAndUnit');
        $field->setMandatory(true);
        $field->setDefaultValue('0');
        $field->setDefaultUnit('unit-1');

        $this->assertTrue(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory input quantity value field with a scalar default of "0" but a configured default unit must get the bypass'
        );
    }

    /**
     * A unit alone, with no configured scalar value at all, must NOT bypass.
     * checkValidity() requires both value and unit to be set for a mandatory
     * field; skipping that check here would let doGetDefaultValue()'s
     * `(null, unit)` default be persisted on a mandatory field.
     */
    public function testQuantityValueFieldWithUnitOnlyIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithUnitOnly');
        $field->setMandatory(true);
        $field->setDefaultUnit('unit-1');

        $this->assertNull($field->getDefaultValue(), 'Sanity check: no scalar default was configured');
        $this->assertFalse(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory quantity value field with only a default unit (no value) must not get the bypass'
        );
    }

    /**
     * setDefaultUnit() accepts an empty string, and doGetDefaultValue()
     * treats that as "no unit" (a truthy check, not a null check). A
     * `!== null` check on the guard's unit side would disagree with that and
     * bypass the mandatory check even though no default actually gets
     * constructed, leaving the field empty on a mandatory field.
     */
    public function testQuantityValueFieldWithZeroDefaultAndEmptyStringUnitIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithZeroDefaultAndEmptyUnit');
        $field->setMandatory(true);
        $field->setDefaultValue(0);
        $field->setDefaultUnit('');

        $this->assertNotNull($field->getDefaultUnit(), 'Sanity check: the stored unit is an empty string, not null');
        $this->assertFalse((bool) $field->getDefaultUnit(), 'An empty string unit must read as falsy, matching doGetDefaultValue()');
        $this->assertFalse(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory quantity value field with a 0 default but an empty string unit must not get the bypass, since doGetDefaultValue() resolves no default here'
        );
    }

    /**
     * QuantityValueRange also exposes getDefaultUnit(), but unlike
     * QuantityValue/InputQuantityValue it has no getDefaultValue() and no
     * default-resolution path at all (it does not use DefaultValueTrait).
     * The default-unit branch must not sweep it in, or a mandatory range
     * field with only a default unit configured could be published empty.
     */
    public function testQuantityValueRangeWithUnitOnlyIsNotSweptIntoTheDefaultUnitBranch(): void
    {
        $field = new QuantityValueRange();
        $field->setName('mandatoryQuantityValueRangeWithUnit');
        $field->setMandatory(true);
        $field->setDefaultUnit('unit-1');

        $this->assertFalse(method_exists($field, 'getDefaultValue'), 'Sanity check: QuantityValueRange has no getDefaultValue()');
        $this->assertFalse(
            $this->mandatoryCheckBypassApplies($field, null),
            'A mandatory QuantityValueRange field with only a default unit must not get the bypass, since it never actually applies a default'
        );
    }

    /**
     * Mirrors the exact bypass condition from Concrete::update(), so these
     * tests stay honest about what production code actually evaluates.
     */
    private function mandatoryCheckBypassApplies(Data $fd, mixed $value): bool
    {
        return $fd->isEmpty($value) &&
            (
                (method_exists($fd, 'getDefaultValue') && !$fd->isEmpty($fd->getDefaultValue()))
                || (method_exists($fd, 'getDefaultValueGenerator') && $fd->getDefaultValueGenerator() !== '')
                || (
                    method_exists($fd, 'getDefaultValue') &&
                    $fd->getDefaultValue() !== null &&
                    method_exists($fd, 'getDefaultUnit') &&
                    $fd->getDefaultUnit()
                )
            );
    }
}
