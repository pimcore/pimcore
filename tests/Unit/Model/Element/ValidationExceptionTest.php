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

namespace Pimcore\Tests\Unit\Model\Element;

use Exception;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Model\Element\ValidationPathSegment;
use Pimcore\Tests\Support\Test\TestCase;
use stdClass;

class ValidationExceptionTest extends TestCase
{
    public function testLeafWithoutStructuredData(): void
    {
        $exception = new ValidationException('Empty mandatory field [ title ]');

        $this->assertNull($exception->getTranslationKey());
        $this->assertSame([], $exception->getTranslationParameters());
        $this->assertNull($exception->getFieldName());
        $this->assertNull($exception->getFieldTitle());
        $this->assertSame([], $exception->getPath());
        $this->assertSame([$exception], $exception->getViolations());
    }

    public function testSetTranslationAcceptsEnumAndCustomKey(): void
    {
        $exception = (new ValidationException('x'))->setTranslation(ValidationMessageKey::MAX_LENGTH, ['max' => 5]);
        $this->assertSame('validation.max_length', $exception->getTranslationKey());
        $this->assertSame(['max' => 5], $exception->getTranslationParameters());

        $exception->setTranslation('my_bundle.custom_rule');
        $this->assertSame('my_bundle.custom_rule', $exception->getTranslationKey());
        $this->assertSame([], $exception->getTranslationParameters(), 'a new translation replaces the parameters');
    }

    public function testSetTranslationKeepsOnlyScalarParametersAndTruncatesStrings(): void
    {
        $exception = (new ValidationException('x'))->setTranslation('key', [
            'string' => str_repeat('ä', 150),
            'int' => 3,
            'float' => 1.5,
            'bool' => false,
            'null' => null,
            'array' => ['a'],
            'object' => new stdClass(),
        ]);

        $parameters = $exception->getTranslationParameters();
        $this->assertSame(['string', 'int', 'float', 'bool', 'null'], array_keys($parameters));
        $this->assertSame(str_repeat('ä', 100) . '…', $parameters['string']);
        $this->assertSame(3, $parameters['int']);
        $this->assertSame(1.5, $parameters['float']);
        $this->assertFalse($parameters['bool']);
        $this->assertNull($parameters['null']);
    }

    public function testSetTranslationDropsNonFiniteFloats(): void
    {
        $exception = (new ValidationException('x'))->setTranslation('key', [
            'inf' => INF,
            'negativeInf' => -INF,
            'nan' => NAN,
            'zero' => 0.0,
        ]);

        $this->assertSame(['zero' => 0.0], $exception->getTranslationParameters());
        $this->assertNotFalse(json_encode($exception->getTranslationParameters()));
    }

    public function testSetTranslationScrubsInvalidUtf8AndStillTruncates(): void
    {
        $exception = (new ValidationException('x'))->setTranslation('key', [
            'short' => "ab\xC3\x28cd",
            'long' => "\xFF" . str_repeat('a', 150),
        ]);

        $parameters = $exception->getTranslationParameters();
        $this->assertTrue(mb_check_encoding($parameters['short'], 'UTF-8'));
        $this->assertStringStartsWith('ab', $parameters['short']);
        $this->assertStringEndsWith('cd', $parameters['short']);

        $this->assertTrue(mb_check_encoding($parameters['long'], 'UTF-8'));
        $this->assertSame(101, mb_strlen($parameters['long']));
        $this->assertStringEndsWith('aaa…', $parameters['long']);
        $this->assertNotFalse(json_encode($parameters));
    }

    public function testSetFieldDoesNotOverwriteAndNormalisesEmptyTitle(): void
    {
        $exception = (new ValidationException('x'))->setField('title', '');
        $this->assertSame('title', $exception->getFieldName());
        $this->assertNull($exception->getFieldTitle());

        $exception->setField('other', 'Other');
        $this->assertSame('title', $exception->getFieldName());
        $this->assertNull($exception->getFieldTitle());
    }

    public function testAggregateCollectsAndFlattensViolations(): void
    {
        $first = new ValidationException('first');
        $second = new ValidationException('second');
        $third = new ValidationException('third');

        $inner = (new ValidationException('inner'))->addViolations($first, $second);
        $outer = (new ValidationException('outer'))->addViolations($inner, $third);

        $this->assertSame([$first, $second], $inner->getViolations());
        $this->assertSame([$first, $second, $third], $outer->getViolations());
    }

    public function testFieldAndPathPropagateToViolations(): void
    {
        $named = (new ValidationException('named'))->setField('name', 'Name');
        $unnamed = new ValidationException('max items');
        $aggregate = (new ValidationException('aggregate'))->addViolations($named, $unnamed);

        $localized = new ValidationPathSegment(field: 'localizedfields', language: 'en');
        $brick = new ValidationPathSegment(field: 'attributes', title: 'Attributes', type: 'SaleInformation');
        $aggregate->addPathSegment($localized)->addPathSegment($brick);
        $aggregate->setField('attributes', 'Attributes');

        $this->assertSame('name', $named->getFieldName(), 'a child field is kept');
        $this->assertSame('attributes', $unnamed->getFieldName(), 'the container becomes the field');
        $this->assertSame('Attributes', $unnamed->getFieldTitle());
        $this->assertSame([$localized, $brick], $named->getPath(), 'innermost first');
        $this->assertSame([$localized, $brick], $unnamed->getPath());
    }

    public function testAddContextDoesNotPropagate(): void
    {
        $leaf = new ValidationException('leaf');
        $aggregate = (new ValidationException('aggregate'))->addViolations($leaf);
        $aggregate->addContext('brick');

        $this->assertSame(['brick'], $aggregate->getContextStack());
        $this->assertSame([], $leaf->getContextStack());
    }

    public function testWithMessageKeepsClassAndStructuredData(): void
    {
        $previous = new Exception('previous');
        $leaf = new ValidationException('leaf');
        $original = (new TestValidationException('original', 7, $previous))
            ->setTranslation(ValidationMessageKey::MANDATORY, ['a' => 'b'])
            ->setField('title', 'Title')
            ->addPathSegment(new ValidationPathSegment(field: 'block', index: 2))
            ->addViolations($leaf);
        $original->addContext('context');
        $original->setSubItems([new Exception('sub')]);

        $copy = $original->withMessage('new message');

        $this->assertInstanceOf(TestValidationException::class, $copy);
        $this->assertSame('new message', $copy->getMessage());
        $this->assertSame(7, $copy->getCode());
        $this->assertSame($previous, $copy->getPrevious());
        $this->assertSame('validation.mandatory', $copy->getTranslationKey());
        $this->assertSame(['a' => 'b'], $copy->getTranslationParameters());
        $this->assertSame('title', $copy->getFieldName());
        $this->assertSame('Title', $copy->getFieldTitle());
        $this->assertEquals($original->getPath(), $copy->getPath());
        $this->assertSame([$leaf], $copy->getViolations());
        $this->assertSame([], $copy->getContextStack(), 'context stack is left to the caller');
        $this->assertSame([], $copy->getSubItems(), 'sub items are left to the caller');
    }

    public function testWithMessageOfLeafReturnsItselfAsViolation(): void
    {
        $copy = (new ValidationException('leaf'))->withMessage('leaf fieldname=title');

        $this->assertSame([$copy], $copy->getViolations());
    }

    public function testLegacyAggregatedMessageIsUnchanged(): void
    {
        $sub = new ValidationException('Empty mandatory field [ key ] (en)');
        $sub->addContext('store');
        $aggregate = new ValidationException('Empty mandatory field [ key ] (en)');
        $aggregate->setSubItems([$sub]);
        $aggregate->addViolations($sub);
        $aggregate->addPathSegment(new ValidationPathSegment(field: 'store', language: 'en'));

        $this->assertSame(
            'Empty mandatory field [ key ] (en) (Empty mandatory field [ key ] (en)[ store ][ store ])',
            $aggregate->getAggregatedMessage()
        );
    }

    public function testPathSegmentToArray(): void
    {
        $segment = new ValidationPathSegment(
            field: 'items',
            title: 'Items',
            index: 0,
            type: 'Feature',
            typeTitle: 'Feature Title'
        );

        $this->assertSame(
            [
                'field' => 'items',
                'title' => 'Items',
                'language' => null,
                'index' => 0,
                'type' => 'Feature',
                'typeTitle' => 'Feature Title',
            ],
            $segment->toArray()
        );

        $this->assertNull((new ValidationPathSegment(field: 'items'))->toArray()['typeTitle']);
    }
}

/**
 * @internal
 */
final class TestValidationException extends ValidationException
{
}
