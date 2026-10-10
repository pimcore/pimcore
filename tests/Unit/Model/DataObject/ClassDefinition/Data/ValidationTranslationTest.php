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

use Pimcore\Model\DataObject\ClassDefinition\Data\Email;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Data\Textarea;
use Pimcore\Model\Element\ValidationException;
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
        $this->assertEquals(['min' => 5, 'value' => 2], $exception->getTranslationParameters());
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

    private function capture(callable $callback): ValidationException
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return $exception;
        }

        $this->fail('Expected a ValidationException');
    }
}
