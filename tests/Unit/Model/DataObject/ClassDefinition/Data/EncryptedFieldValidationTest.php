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

use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The plain value of an encrypted field must never end up in the translation parameters of a validation error.
 */
class EncryptedFieldValidationTest extends TestCase
{
    public function testRegexMismatchDoesNotExposeTheValue(): void
    {
        $delegate = new Input();
        $delegate->setName('secret');
        $delegate->setRegex('^[0-9]+$');

        // the delegate alone reports the value, which is what the wrapper has to strip
        try {
            $delegate->checkValidity('s3cret-plain');
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('s3cret-plain', $exception->getTranslationParameters()['value']);
        }

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity('s3cret-plain');
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(ValidationMessageKey::REGEX_MISMATCH->value, $exception->getTranslationKey());
            $this->assertSame(['regex' => '^[0-9]+$'], $exception->getTranslationParameters());
        }
    }

    public function testMandatoryErrorKeepsItsTranslationKey(): void
    {
        $delegate = new Input();
        $delegate->setName('secret');
        $delegate->setMandatory(true);

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity('');
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(ValidationMessageKey::MANDATORY->value, $exception->getTranslationKey());
            $this->assertSame([], $exception->getTranslationParameters());
        }
    }
}
