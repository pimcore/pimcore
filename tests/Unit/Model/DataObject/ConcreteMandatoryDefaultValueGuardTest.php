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

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\OptionsProvider\DefaultingOptionsProvider;
use Pimcore\Tests\Support\Util\OptionsProvider\NoDefaultOptionsProvider;

/**
 * Regression test for PEES-1279.
 *
 * Concrete::update() decides whether to skip the mandatory check on object
 * creation for a field left empty, by asking the field definition via
 * Data::hasApplicableDefaultValue() whether a real default will actually
 * be applied to that field. This test calls that production method
 * directly - not a test-local copy of its logic - so it stays
 * honest if the real condition changes.
 *
 * Concrete::update() itself can only be exercised end-to-end through a full
 * save/publish integration test; see
 * ObjectTest::testDefaultValueAndMandatorySavedToVersion(),
 * ObjectTest::testMandatoryZeroAndFalseDefaultsSavedToVersion()
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
     * Regression guard: a check that only looked at `$fd->getDefaultValue() !== null`
     * could treat an empty select default as "has a default" and incorrectly bypass
     * the mandatory check, potentially letting a mandatory field stay empty on
     * publish. The select stores an empty default as null, and the check must still
     * not recognise it as a default.
     */
    public function testSelectFieldWithEmptyStringDefaultIsNotRecognizedAsHavingADefault(): void
    {
        $field = new Select();
        $field->setName('mandatorySelectWithEmptyDefault');
        $field->setMandatory(true);
        $field->setDefaultValue('');

        $this->assertNull($field->getDefaultValue(), 'Sanity check: an empty default is stored as null');
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
     * A non-zero scalar default with no unit configured keeps the bypass it
     * has always had. doGetDefaultValue() constructs an incomplete
     * (value, null) default from it, which checkValidity() would reject on a
     * mandatory field - so arguably it should not bypass. But it did before
     * this fix, and withdrawing it would turn a save that used to succeed
     * into a hard ValidationException on a non-major line. That tightening
     * needs its own deprecation path, not a side effect of the 0/false fix.
     */
    public function testQuantityValueFieldWithValueOnlyKeepsItsPreExistingBypass(): void
    {
        $field = new QuantityValue();
        $field->setName('mandatoryQuantityValueWithValueOnly');
        $field->setMandatory(true);
        $field->setDefaultValue(5);

        $this->assertNull($field->getDefaultUnit(), 'Sanity check: no unit was configured');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory quantity value field with a truthy scalar default must keep the bypass it had before this fix'
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

    public function testMandatoryDateWithCurrentDateIsRecognizedAsHavingADefault(): void
    {
        $field = new Date();
        $field->setName('mandatoryDateWithCurrentDate');
        $field->setMandatory(true);
        $field->setUseCurrentDate(true);

        $this->assertFalse((bool) $field->getDefaultValue(), 'Sanity check: no default value is configured');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory date field that defaults to the current date must get the create-time mandatory-check bypass'
        );
    }

    public function testMandatoryDatetimeWithCurrentDateIsRecognizedAsHavingADefault(): void
    {
        $field = new Datetime();
        $field->setName('mandatoryDatetimeWithCurrentDate');
        $field->setMandatory(true);
        $field->setUseCurrentDate(true);

        $this->assertFalse((bool) $field->getDefaultValue(), 'Sanity check: no default value is configured');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory datetime field that defaults to the current date must get the create-time mandatory-check bypass'
        );
    }

    public function testMandatoryDateAndDatetimeWithoutAnyDefaultAreNotRecognizedAsHavingADefault(): void
    {
        foreach ([new Date(), new Datetime()] as $field) {
            $field->setMandatory(true);

            $this->assertFalse(
                $this->fieldHasApplicableDefault($field),
                $field::class . ' without a default value and without "use current date" must not get the bypass'
            );
        }
    }

    /**
     * The hook reports whether a default will be applied, independent of
     * whether the field happens to be mandatory - the same as it does for a
     * configured default.
     */
    public function testRuntimeDefaultIsReportedRegardlessOfTheMandatoryFlag(): void
    {
        $date = new Date();
        $date->setUseCurrentDate(true);

        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(DefaultingOptionsProvider::class);

        foreach ([$date, $select] as $field) {
            $field->setMandatory(false);

            $this->assertTrue(
                $this->fieldHasApplicableDefault($field),
                $field::class . ' resolves a default, so it must be reported whether or not it is mandatory'
            );
        }
    }

    public function testMandatorySelectWithProviderDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Select();
        $field->setName('mandatorySelectWithProviderDefault');
        $field->setMandatory(true);
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $field->setOptionsProviderClass(DefaultingOptionsProvider::class);

        $this->assertEmpty($field->getDefaultValue(), 'Sanity check: no default value is configured');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory select field whose options provider supplies a default must get the create-time mandatory-check bypass'
        );
    }

    public function testMandatoryMultiselectWithProviderDefaultIsRecognizedAsHavingADefault(): void
    {
        $field = new Multiselect();
        $field->setName('mandatoryMultiselectWithProviderDefault');
        $field->setMandatory(true);
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $field->setOptionsProviderClass(DefaultingOptionsProvider::class);

        $this->assertEmpty($field->getDefaultValue(), 'Sanity check: no default value is configured');
        $this->assertTrue(
            $this->fieldHasApplicableDefault($field),
            'A mandatory multiselect field whose options provider supplies a default must get the create-time mandatory-check bypass'
        );
    }

    /**
     * The bypass has to follow the *resolved* default: a provider that
     * does not come up with a default must not waive the mandatory check,
     * otherwise an empty mandatory field would be persisted.
     */
    public function testProviderThatResolvesNoDefaultDoesNotGetTheBypass(): void
    {
        foreach ([new Select(), new Multiselect()] as $field) {
            $field->setMandatory(true);
            $field->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
            $field->setOptionsProviderClass(NoDefaultOptionsProvider::class);

            $this->assertFalse(
                $this->fieldHasApplicableDefault($field),
                $field::class . ' whose options provider resolves no default must not get the bypass'
            );
        }
    }

    /**
     * The guard must never be *stricter* than the condition it replaced. Every
     * field whose configured default qualified for the bypass under the pre-fix
     * `!empty($fd->getDefaultValue())` test must still qualify, otherwise an
     * object that saved successfully before would start throwing a
     * ValidationException - a behaviour break on a non-major line.
     *
     * The pre-fix condition is deliberately reproduced here rather than read
     * from production code: it is a frozen historical contract, not something
     * that should follow future edits to the guard.
     */
    public function testGuardNeverWithdrawsABypassThePreFixConditionGranted(): void
    {
        $asserted = 0;

        foreach ($this->fieldDefinitionsUnderTest() as $label => $field) {
            if (!$this->qualifiedBeforeTheFix($field)) {
                continue;
            }

            $asserted++;
            $this->assertTrue(
                $this->fieldHasApplicableDefault($field),
                sprintf('%s qualified for the mandatory-check bypass before this fix and must still qualify', $label)
            );
        }

        $this->assertGreaterThan(0, $asserted, 'Sanity check: the sample must contain fields that qualified before the fix');
    }

    /**
     * The condition `Concrete::update()` used before this fix.
     */
    private function qualifiedBeforeTheFix(Data $fd): bool
    {
        return (method_exists($fd, 'getDefaultValue') && !empty($fd->getDefaultValue()))
            || (method_exists($fd, 'getDefaultValueGenerator') && $fd->getDefaultValueGenerator() !== '');
    }

    /**
     * @return array<string, Data>
     */
    private function fieldDefinitionsUnderTest(): array
    {
        $numericZero = (new Numeric())->setDefaultValue(0);
        $numericFive = (new Numeric())->setDefaultValue(5);
        $checkboxFalse = (new Checkbox())->setDefaultValue(0);
        $selectEmpty = new Select();
        $selectEmpty->setDefaultValue('');

        $selectOption = new Select();
        $selectOption->setDefaultValue('someOption');

        $quantityValueOnly = new QuantityValue();
        $quantityValueOnly->setDefaultValue(5);

        $quantityZeroPlusUnit = new QuantityValue();
        $quantityZeroPlusUnit->setDefaultValue(0);
        $quantityZeroPlusUnit->setDefaultUnit('unit-1');

        $quantityUnitOnly = new QuantityValue();
        $quantityUnitOnly->setDefaultUnit('unit-1');

        $quantityZeroPlusEmptyUnit = new QuantityValue();
        $quantityZeroPlusEmptyUnit->setDefaultValue(0);
        $quantityZeroPlusEmptyUnit->setDefaultUnit('');

        $inputQuantityZeroPlusUnit = new InputQuantityValue();
        $inputQuantityZeroPlusUnit->setDefaultValue('0');
        $inputQuantityZeroPlusUnit->setDefaultUnit('unit-1');

        $range = new QuantityValueRange();
        $range->setDefaultUnit('unit-1');

        $dateConfigured = new Date();
        $dateConfigured->setDefaultValue(1700000000);

        $datetimeConfigured = new Datetime();
        $datetimeConfigured->setDefaultValue(1700000000);

        $selectWithProvider = new Select();
        $selectWithProvider->setDefaultValue('someOption');
        $selectWithProvider->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $selectWithProvider->setOptionsProviderClass(NoDefaultOptionsProvider::class);

        return [
            'Numeric with a 0 default' => $numericZero,
            'Numeric with a 5 default' => $numericFive,
            'Checkbox with a false default' => $checkboxFalse,
            'Select with an empty-string default' => $selectEmpty,
            'Select with an option default' => $selectOption,
            'QuantityValue with a value but no unit' => $quantityValueOnly,
            'QuantityValue with a 0 value and a unit' => $quantityZeroPlusUnit,
            'QuantityValue with a unit but no value' => $quantityUnitOnly,
            'QuantityValue with a 0 value and an empty-string unit' => $quantityZeroPlusEmptyUnit,
            'InputQuantityValue with a "0" value and a unit' => $inputQuantityZeroPlusUnit,
            'QuantityValueRange with a unit' => $range,
            'Date with a configured default' => $dateConfigured,
            'Datetime with a configured default' => $datetimeConfigured,
            'Select with a configured default and a provider that resolves none' => $selectWithProvider,
        ];
    }

    private function fieldHasApplicableDefault(Data $fd): bool
    {
        // ClassDefinition is final and so cannot be stubbed, options providers get it via the object
        $object = $this->createStub(Concrete::class);
        $object->method('getClass')->willReturn(new ClassDefinition());

        return $fd->hasApplicableDefaultValue($object);
    }
}
