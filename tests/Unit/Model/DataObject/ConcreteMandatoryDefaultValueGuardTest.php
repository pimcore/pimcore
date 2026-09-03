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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * Regression test for PEES-1279.
 *
 * Concrete::update() decides whether to skip the mandatory check on object
 * creation for a field left empty, by asking
 * Concrete::fieldHasApplicableDefault() whether a real default will actually
 * be applied to that field. This test calls that private production method
 * directly via reflection - not a test-local copy of its logic - so it stays
 * honest if the real condition changes.
 *
 * Concrete::update() itself can only be exercised end-to-end through a full
 * save/publish integration test; see
 * ObjectTest::testDefaultValueAndMandatorySavedToVersion(),
 * ObjectTest::testMandatoryZeroAndFalseDefaultsSavedToVersion() and
 * ObjectTest::testMandatoryQuantityValueZeroPlusUnitDefaultSavedToVersion()
 * for that coverage.
 */
class ConcreteMandatoryDefaultValueGuardTest extends TestCase
{
    public function testNumericFieldWithZeroDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Numeric();
        $field->setName('mandatoryNumericWithZeroDefault');
        $field->setMandatory(true);
        $field->setDefaultValue(0);

        $this->assertFalse($field->isEmpty(0), 'Sanity check: a numeric value of 0 must not be treated as empty');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory numeric field with a default of 0 must get the create-time mandatory-check bypass'
        );
    }

    public function testCheckboxFieldWithFalseDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Checkbox();
        $field->setName('mandatoryCheckboxWithFalseDefault');
        $field->setMandatory(true);
        $field->setDefaultValue(0);

        $this->assertFalse($field->isEmpty(false), 'Sanity check: a checkbox value of false must not be treated as empty');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory checkbox with a default of false must get the create-time mandatory-check bypass'
        );
    }

    /**
     * Regression guard: a naive `$fd->getDefaultValue() !== null` check (an
     * earlier draft of this fix) would have treated a Select field's empty
     * string default as "has a default" and incorrectly bypassed the
     * mandatory check, potentially letting a mandatory field stay empty on
     * publish.
     */
    public function testSelectFieldWithEmptyStringDefaultIsNotRecognizedAsHavingADefault(): void
    {
        $field = new Select();
        $field->setName('mandatorySelectWithEmptyDefault');
        $field->setMandatory(true);
        $field->setDefaultValue('');

        $this->assertNotNull($field->getDefaultValue(), 'Sanity check: the stored default is an empty string, not null');
        $this->assertFalse(
            $this->fieldHasApplicableDefault($field),
            'A mandatory select field with a genuinely empty default must not get the create-time mandatory-check bypass'
        );
    }

    /**
     * QuantityValue::doGetDefaultValue() constructs a default from
     * `getDefaultValue() || getDefaultUnit()` — so a scalar default of 0
     * combined with a configured default unit still resolves to a real
     * default (value=0, unit=<configured>), even though the scalar value
     * alone reads as empty.
     */
    public function testQuantityValueFieldWithZeroDefaultAndUnitIsRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithZeroDefaultAndUnit');
        $field->setMandatory(true);
        $field->setDefaultValue(0);
        $field->setDefaultUnit('unit-1');

        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with a scalar default of 0 but a configured default unit must get the create-time mandatory-check bypass'
        );
    }

    public function testQuantityValueFieldWithNoDefaultIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithNoDefault');
        $field->setMandatory(true);

        $this->assertFalse(
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with neither a default value nor a default unit must not get the bypass'
        );
    }

    /**
     * A non-zero scalar default alone, with no unit configured, must NOT
     * bypass. doGetDefaultValue() would still construct an incomplete
     * (value, null) default, which checkValidity() requires a unit for on a
     * mandatory field - bypassing here would let that incomplete data
     * persist unchecked.
     */
    public function testQuantityValueFieldWithValueOnlyIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithValueOnly');
        $field->setMandatory(true);
        $field->setDefaultValue(5);

        $this->assertNull($field->getDefaultUnit(), 'Sanity check: no unit was configured');
        $this->assertFalse(
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with a non-zero scalar default but no unit must not get the bypass'
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
            $this->fieldHasApplicableDefault($field),
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
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with only a default unit (no value) must not get the bypass'
        );
    }

    /**
     * setDefaultUnit() accepts an empty string, and doGetDefaultValue()
     * treats that as "no unit" (a truthy check, not a null check). Treating
     * '' as a configured unit here would bypass the mandatory check even
     * though no default actually gets constructed, leaving the field empty
     * on a mandatory field.
     */
    public function testQuantityValueFieldWithZeroDefaultAndEmptyStringUnitIsNotRecognizedAsHavingADefault(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithZeroDefaultAndEmptyUnit');
        $field->setMandatory(true);
        $field->setDefaultValue(0);
        $field->setDefaultUnit('');

        $this->assertNotNull($field->getDefaultUnit(), 'Sanity check: the stored unit is an empty string, not null');
        $this->assertFalse((bool) $field->getDefaultUnit(), 'Sanity check: an empty string unit must read as falsy, matching doGetDefaultValue()');
        $this->assertFalse(
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with a 0 default but an empty string unit must not get the bypass'
        );
    }

    /**
     * QuantityValueRange also exposes getDefaultUnit(), but unlike
     * QuantityValue/InputQuantityValue it has no getDefaultValue() and no
     * default-resolution path at all (it does not use DefaultValueTrait).
     * A mandatory range field with only a default unit configured must not
     * get the bypass, since it never actually applies a default.
     */
    public function testQuantityValueRangeWithUnitOnlyIsNotSweptIntoTheDefaultUnitBranch(): void
    {
        $field = new QuantityValueRange();
        $field->setName('mandatoryQuantityValueRangeWithUnit');
        $field->setMandatory(true);
        $field->setDefaultUnit('unit-1');

        $this->assertFalse(method_exists($field, 'getDefaultValue'), 'Sanity check: QuantityValueRange has no getDefaultValue()');
        $this->assertFalse(
            $this->fieldHasApplicableDefault($field),
            'A mandatory QuantityValueRange field with only a default unit must not get the bypass'
        );
    }

    private function fieldHasApplicableDefault(Data $fd): bool
    {
        $method = new ReflectionMethod(Concrete::class, 'fieldHasApplicableDefault');
        $method->setAccessible(true);

        return $method->invoke(null, $fd);
    }
}
