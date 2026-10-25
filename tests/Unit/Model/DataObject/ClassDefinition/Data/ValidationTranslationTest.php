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

use Carbon\CarbonPeriod;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Email;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Link;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\NumericRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Data\Slider;
use Pimcore\Model\DataObject\ClassDefinition\Data\Textarea;
use Pimcore\Model\DataObject\ClassDefinition\Data\Time;
use Pimcore\Model\DataObject\Data\Link as LinkValue;
use Pimcore\Model\DataObject\Data\NumericRange as NumericRangeValue;
use Pimcore\Model\DataObject\Data\QuantityValue as QuantityValueValue;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The data type validators keep their legacy message and additionally carry a translation key with parameters.
 */
class ValidationTranslationTest extends TestCase
{
    public function testMandatoryCheck(): void
    {
        $field = new Input();
        $field->setName('title');
        $field->setMandatory(true);

        $exception = $this->capture(static fn () => $field->checkValidity(''));

        $this->assertSame('Empty mandatory field [ title ]', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MANDATORY->value, $exception->getTranslationKey());
        $this->assertSame([], $exception->getTranslationParameters());
    }

    public function testInputRegexMismatch(): void
    {
        $field = new Input();
        $field->setName('code');
        $field->setRegex('^[0-9]+$');

        $exception = $this->capture(static fn () => $field->checkValidity('abc'));

        $this->assertSame(
            "Value in field [ code ] doesn't match input validation '^[0-9]+$'",
            $exception->getMessage()
        );
        $this->assertSame(ValidationMessageKey::REGEX_MISMATCH->value, $exception->getTranslationKey());
        $this->assertSame(['regex' => '^[0-9]+$', 'value' => 'abc'], $exception->getTranslationParameters());
    }

    public function testInputColumnLength(): void
    {
        $field = new Input();
        $field->setName('short');
        $field->setColumnLength(3);

        $exception = $this->capture(static fn () => $field->checkValidity('abcd'));

        $this->assertSame('Value in field [ short ] is longer than 3 characters', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_LENGTH->value, $exception->getTranslationKey());
        $this->assertSame(['max' => 3], $exception->getTranslationParameters());
    }

    public function testNumericMinValue(): void
    {
        $field = new Numeric();
        $field->setName('amount');
        $field->setMinValue(5);

        $exception = $this->capture(static fn () => $field->checkValidity(2));

        $this->assertSame('Value in field [ amount ] is not at least 5', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MIN_VALUE->value, $exception->getTranslationKey());
        $this->assertEquals(['min' => 5.0, 'value' => 2], $exception->getTranslationParameters());
    }

    public function testNumericMaxValue(): void
    {
        $field = new Numeric();
        $field->setName('amount');
        $field->setMaxValue(5);

        $exception = $this->capture(static fn () => $field->checkValidity(9));

        $this->assertSame('Value in field [ amount ] is bigger than 5', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_VALUE->value, $exception->getTranslationKey());
        $this->assertEquals(['max' => 5, 'value' => 9], $exception->getTranslationParameters());
    }

    public function testNumericNotUnsigned(): void
    {
        $field = new Numeric();
        $field->setName('amount');
        $field->setUnsigned(true);

        $exception = $this->capture(static fn () => $field->checkValidity(-1));

        $this->assertSame('Value in field [ amount ] is not unsigned (bigger than 0)', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::NOT_UNSIGNED->value, $exception->getTranslationKey());
    }

    public function testTextareaMaxLength(): void
    {
        $field = new Textarea();
        $field->setName('notes');
        $field->setMaxLength(2);

        $exception = $this->capture(static fn () => $field->checkValidity('abc'));

        $this->assertSame("Value in field [ notes ] longer than max length of '2'", $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_LENGTH->value, $exception->getTranslationKey());
        $this->assertSame(['max' => 2], $exception->getTranslationParameters());
    }

    public function testEmail(): void
    {
        $field = new Email();
        $field->setName('mail');

        $exception = $this->capture(static fn () => $field->checkValidity('not-an-email'));

        $this->assertSame("Value in field [ mail ] isn't a valid email address", $exception->getMessage());
        $this->assertSame(ValidationMessageKey::INVALID_EMAIL->value, $exception->getTranslationKey());
        $this->assertSame(['value' => 'not-an-email'], $exception->getTranslationParameters());
    }

    public function testSelectInvalidOption(): void
    {
        $field = new Select();
        $field->setName('color');
        $field->setOptions([['key' => 'Red', 'value' => 'red']]);
        $field->setEnforceValidation(true);

        $exception = $this->capture(static fn () => $field->checkValidity('green'));

        $this->assertSame("Invalid option 'green' for field [ color ]", $exception->getMessage());
        $this->assertSame(ValidationMessageKey::INVALID_OPTION->value, $exception->getTranslationKey());
        $this->assertSame(['value' => 'green'], $exception->getTranslationParameters());
    }

    public function testPasswordNeverExposesTheValue(): void
    {
        $field = new Password();
        $field->setName('secret');
        $field->setMinimumLength(10);

        $exception = $this->capture(static fn () => $field->checkValidity('hunter2'));

        $this->assertSame('Value in field [ secret ] is not at least 10 characters', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MIN_LENGTH->value, $exception->getTranslationKey());
        $this->assertSame(['min' => 10], $exception->getTranslationParameters());
        $this->assertArrayNotHasKey('value', $exception->getTranslationParameters());
    }

    public function testPasswordTooLongNeverExposesTheValue(): void
    {
        $field = new Password();
        $field->setName('secret');

        $exception = $this->capture(static fn () => $field->checkValidity(str_repeat('a', 5000)));

        $this->assertSame('Value in field [ secret ] is too long', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_LENGTH->value, $exception->getTranslationKey());
        $this->assertArrayNotHasKey('value', $exception->getTranslationParameters());
    }

    public function testMultiselectInvalidOption(): void
    {
        $field = new Multiselect();
        $field->setName('colors');
        $field->setOptions([['key' => 'Red', 'value' => 'red']]);
        $field->setEnforceValidation(true);

        $exception = $this->capture(static fn () => $field->checkValidity(['red', 'green']));

        $this->assertSame("Invalid multiselect option 'green' on field [ colors ]", $exception->getMessage());
        $this->assertSame(ValidationMessageKey::INVALID_OPTION->value, $exception->getTranslationKey());
        $this->assertSame(['value' => 'green'], $exception->getTranslationParameters());
    }

    public function testSliderNotNumeric(): void
    {
        $field = new Slider();
        $field->setName('level');

        $exception = $this->capture(static fn () => $field->checkValidity('high'));

        $this->assertSame('invalid slider data', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::NOT_NUMERIC->value, $exception->getTranslationKey());
        $this->assertSame(['value' => 'high'], $exception->getTranslationParameters());
    }

    public function testTimeInvalidFormat(): void
    {
        $field = new Time();
        $field->setName('opening');

        $exception = $this->capture(static fn () => $field->checkValidity('25:99'));

        $this->assertSame(
            'Wrong time format given must be a 5 digit string (eg: 06:49) [ opening ]',
            $exception->getMessage()
        );
        $this->assertSame(ValidationMessageKey::INVALID_TIME->value, $exception->getTranslationKey());
        $this->assertSame(['value' => '25:99'], $exception->getTranslationParameters());
    }

    public function testTimeMinAndMaxValue(): void
    {
        $field = new Time();
        $field->setName('opening');
        $field->setMinValue('08:00');
        $field->setMaxValue('18:00');

        $tooEarly = $this->capture(static fn () => $field->checkValidity('07:30'));
        $this->assertSame(ValidationMessageKey::MIN_VALUE->value, $tooEarly->getTranslationKey());
        $this->assertSame(['min' => '08:00', 'value' => '07:30'], $tooEarly->getTranslationParameters());

        $tooLate = $this->capture(static fn () => $field->checkValidity('19:00'));
        $this->assertSame(ValidationMessageKey::MAX_VALUE->value, $tooLate->getTranslationKey());
        $this->assertSame(['max' => '18:00', 'value' => '19:00'], $tooLate->getTranslationParameters());
    }

    public function testNumericRangeLimits(): void
    {
        $field = new NumericRange();
        $field->setName('span');
        $field->setMinValue(5);
        $field->setMaxValue(10);

        $unit = new Unit();
        $unit->setId('kg');

        $minBelow = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(2, 8)));
        $this->assertSame('Minimum value in field [ span ] is not at least 5', $minBelow->getMessage());
        $this->assertSame(ValidationMessageKey::MIN_VALUE->value, $minBelow->getTranslationKey());
        $this->assertSame(['min' => 5.0, 'value' => 2], $minBelow->getTranslationParameters());

        $maxBelow = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(6, 4)));
        $this->assertSame('Maximum value in field [ span ] is not at least 5', $maxBelow->getMessage());
        $this->assertSame(ValidationMessageKey::MIN_VALUE->value, $maxBelow->getTranslationKey());
        $this->assertSame(['min' => 5.0, 'value' => 4], $maxBelow->getTranslationParameters());

        $minAbove = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(11, 12)));
        $this->assertSame('Minimum value in field [ span ] is bigger than 10', $minAbove->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_VALUE->value, $minAbove->getTranslationKey());
        $this->assertSame(['max' => 10.0, 'value' => 11], $minAbove->getTranslationParameters());

        $maxAbove = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(6, 12)));
        $this->assertSame('Maximum value in field [ span ] is bigger than 10', $maxAbove->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_VALUE->value, $maxAbove->getTranslationKey());
        $this->assertSame(['max' => 10.0, 'value' => 12], $maxAbove->getTranslationParameters());
    }

    public function testNumericRangeStartAfterEnd(): void
    {
        $field = new NumericRange();
        $field->setName('span');

        $exception = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(9, 3)));

        $this->assertSame('Minimum value in field [ span ] is bigger than the maximum value', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::RANGE_START_AFTER_END->value, $exception->getTranslationKey());
        $this->assertSame([], $exception->getTranslationParameters());
    }

    public function testNumericRangeNotIntegerAndNotUnsigned(): void
    {
        $field = new NumericRange();
        $field->setName('span');
        $field->setInteger(true);

        $notInteger = $this->capture(static fn () => $field->checkValidity(new NumericRangeValue(1.5, 3)));
        $this->assertSame(
            'Either the minimum or maximum value in field [ span ] is not an integer',
            $notInteger->getMessage()
        );
        $this->assertSame(ValidationMessageKey::NOT_INTEGER->value, $notInteger->getTranslationKey());
        $this->assertSame(['value' => '[1.5, 3]'], $notInteger->getTranslationParameters());

        $unsignedField = new NumericRange();
        $unsignedField->setName('span');
        $unsignedField->setUnsigned(true);

        $notUnsigned = $this->capture(static fn () => $unsignedField->checkValidity(new NumericRangeValue(-3, 4)));
        $this->assertSame(ValidationMessageKey::NOT_UNSIGNED->value, $notUnsigned->getTranslationKey());
        $this->assertSame(['value' => -3], $notUnsigned->getTranslationParameters());
    }

    public function testDateRangeStartAfterEnd(): void
    {
        $field = new DateRange();
        $field->setName('stay');

        $period = CarbonPeriod::create('2026-05-10', '2026-05-01');

        $exception = $this->capture(static fn () => $field->checkValidity($period));

        $this->assertSame('Start value in field [ stay ] is bigger than the end value', $exception->getMessage());
        $this->assertSame(ValidationMessageKey::RANGE_START_AFTER_END->value, $exception->getTranslationKey());
        $this->assertSame([], $exception->getTranslationParameters());
    }

    public function testQuantityValueNotNumericAndLimits(): void
    {
        $field = new QuantityValue();
        $field->setName('weight');
        $field->setMinValue(2);
        $field->setMaxValue(10);

        $unit = new Unit();
        $unit->setId('kg');

        $notNumeric = $this->capture(static fn () => $field->checkValidity(new QuantityValueValue('heavy', $unit)));
        $this->assertSame(ValidationMessageKey::NOT_NUMERIC->value, $notNumeric->getTranslationKey());
        $this->assertSame(['value' => 'heavy'], $notNumeric->getTranslationParameters());

        $tooLow = $this->capture(static fn () => $field->checkValidity(new QuantityValueValue(1, $unit)));
        $this->assertSame('Value in field [ weight ] is not at least 2', $tooLow->getMessage());
        $this->assertSame(ValidationMessageKey::MIN_VALUE->value, $tooLow->getTranslationKey());
        $this->assertSame(['min' => 2.0, 'value' => 1], $tooLow->getTranslationParameters());

        $tooHigh = $this->capture(static fn () => $field->checkValidity(new QuantityValueValue(11, $unit)));
        $this->assertSame('Value in field [ weight ] is bigger than 10', $tooHigh->getMessage());
        $this->assertSame(ValidationMessageKey::MAX_VALUE->value, $tooHigh->getTranslationKey());
        $this->assertSame(['max' => 10.0, 'value' => 11], $tooHigh->getTranslationParameters());
    }

    public function testImageMandatoryAndInvalid(): void
    {
        $field = new Image();
        $field->setName('picture');
        $field->setMandatory(true);

        $mandatory = $this->capture(static fn () => $field->checkValidity(null));
        $this->assertSame('Empty mandatory field [ picture ]', $mandatory->getMessage());
        $this->assertSame(ValidationMessageKey::MANDATORY->value, $mandatory->getTranslationKey());

        $field->setMandatory(false);
        $invalid = $this->capture(static fn () => $field->checkValidity(new Asset\Document()));
        $this->assertSame('Invalid data in field `picture`', $invalid->getMessage());
        $this->assertSame(ValidationMessageKey::INVALID_RELATION->value, $invalid->getTranslationKey());
        $this->assertSame([], $invalid->getTranslationParameters());
    }

    public function testManyToOneRelationMandatoryAndInvalid(): void
    {
        $field = new ManyToOneRelation();
        $field->setName('owner');
        $field->setMandatory(true);

        $mandatory = $this->capture(static fn () => $field->checkValidity(null));
        $this->assertSame('Empty mandatory field [ owner ]', $mandatory->getMessage());
        $this->assertSame(ValidationMessageKey::MANDATORY->value, $mandatory->getTranslationKey());

        $invalid = $this->capture(static fn () => $field->checkValidity('not an element'));
        $this->assertSame(
            'Invalid data in field `owner` [type: ' . $field->getFieldtype() . ']',
            $invalid->getMessage()
        );
        $this->assertSame(ValidationMessageKey::INVALID_RELATION->value, $invalid->getTranslationKey());
        $this->assertSame([], $invalid->getTranslationParameters());
    }

    public function testManyToManyRelationMandatoryInvalidAndMaxItems(): void
    {
        $field = new ManyToManyRelation();
        $field->setName('related');
        $field->setMandatory(true);

        $mandatory = $this->capture(static fn () => $field->checkValidity([]));
        $this->assertSame('Empty mandatory field [ related ]', $mandatory->getMessage());
        $this->assertSame(ValidationMessageKey::MANDATORY->value, $mandatory->getTranslationKey());

        $field->setMandatory(false);
        $notAllowed = new Asset\Document();
        $notAllowed->setId(1);
        $invalid = $this->capture(static fn () => $field->checkValidity([$notAllowed]));
        $this->assertSame(
            'Invalid relation in field `related` [type: ' . $field->getFieldtype() . ']',
            $invalid->getMessage()
        );
        $this->assertSame(ValidationMessageKey::INVALID_RELATION->value, $invalid->getTranslationKey());

        $field->setAssetsAllowed(true);
        $field->setMaxItems(1);
        $first = new Asset\Document();
        $first->setId(1);
        $second = new Asset\Document();
        $second->setId(2);
        $tooMany = $this->capture(static fn () => $field->checkValidity([$first, $second]));
        $this->assertSame(
            'Number of allowed relations in field `related` exceeded (max. 1)',
            $tooMany->getMessage()
        );
        $this->assertSame(ValidationMessageKey::MAX_RELATIONS->value, $tooMany->getTranslationKey());
        $this->assertSame(['max' => 1], $tooMany->getTranslationParameters());
    }

    public function testLinkReferencingMissingElements(): void
    {
        $field = new Link();
        $field->setName('target');

        foreach (['document', 'asset'] as $type) {
            $link = new LinkValue();
            $link->setInternalType($type);
            $link->setInternal(2147483000);

            $exception = $this->capture(static fn () => $field->checkValidity($link));
            $this->assertSame(
                'invalid internal link, referenced document with id [2147483000] does not exist',
                $exception->getMessage()
            );
            $this->assertSame(ValidationMessageKey::INVALID_RELATION->value, $exception->getTranslationKey());
            $this->assertSame([], $exception->getTranslationParameters());
        }
    }

    private function capture(callable $callback): StructuredValidationException
    {
        try {
            $callback();
        } catch (StructuredValidationException $exception) {
            return $exception;
        }

        $this->fail('Expected a StructuredValidationException');
    }
}
