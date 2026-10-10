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
use Pimcore\Model\Element\StructuredValidationException;
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
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame('s3cret-plain', $exception->getTranslationParameters()['value']);
        }

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity('s3cret-plain');
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame(ValidationMessageKey::REGEX_MISMATCH->value, $exception->getTranslationKey());
            $this->assertSame(['regex' => '^[0-9]+$'], $exception->getTranslationParameters());
        }
    }

    public function testValueIsStrippedFromNestedViolations(): void
    {
        $delegate = new class() extends Input {
            public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
            {
                $leaf = (new StructuredValidationException('leaf'))
                    ->setTranslation(ValidationMessageKey::REGEX_MISMATCH, ['regex' => '^a$', 'value' => $data]);

                throw (new StructuredValidationException('aggregate'))
                    ->setTranslation('custom.aggregate', ['value' => $data])
                    ->addViolations($leaf);
            }
        };
        $delegate->setName('secret');

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity('s3cret-plain');
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame([], $exception->getTranslationParameters());
            $this->assertCount(1, $exception->getViolations());
            $leaf = $exception->getViolations()[0];
            $this->assertSame(ValidationMessageKey::REGEX_MISMATCH->value, $leaf->getTranslationKey());
            $this->assertSame(['regex' => '^a$'], $leaf->getTranslationParameters());
        }
    }

    public function testPlainValueUnderAnotherNameIsStripped(): void
    {
        $plain = str_repeat('s3cret-', 20);
        $delegate = new class() extends Input {
            public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
            {
                throw (new StructuredValidationException('custom'))
                    ->setTranslation('custom.rule', ['given' => $data, 'echo' => $data, 'max' => 5]);
            }
        };
        $delegate->setName('secret');

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity($plain);
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            // also the truncated copy of a long value is recognised
            $this->assertSame(['max' => 5], $exception->getTranslationParameters());
        }
    }

    public function testShortPlainValueUnderAnotherNameIsStrippedButLimitsAreKept(): void
    {
        $delegate = new class() extends Input {
            public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
            {
                throw (new StructuredValidationException('custom'))
                    ->setTranslation('custom.rule', ['given' => $data, 'min' => 3, 'hint' => 'a…', 'flag' => true]);
            }
        };
        $delegate->setName('secret');

        $field = new EncryptedField();
        $field->setName('secret');
        $field->delegate = $delegate;

        try {
            $field->checkValidity('3');
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame(['min' => 3, 'hint' => 'a…', 'flag' => true], $exception->getTranslationParameters());
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
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertSame(ValidationMessageKey::MANDATORY->value, $exception->getTranslationKey());
            $this->assertSame([], $exception->getTranslationParameters());
        }
    }
}
