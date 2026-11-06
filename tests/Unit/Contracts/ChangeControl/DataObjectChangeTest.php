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

namespace Pimcore\Tests\Unit\Contracts\ChangeControl;

use InvalidArgumentException;
use Pimcore\Contracts\ChangeControl\DataObjectChange;
use Pimcore\Contracts\ChangeControl\ElementRef;
use Pimcore\Contracts\ChangeControl\FieldKind;
use Pimcore\Contracts\ChangeControl\FieldValue;
use Pimcore\Contracts\ChangeControl\NewDataObject;
use Pimcore\Tests\Support\Test\TestCase;

class DataObjectChangeTest extends TestCase
{
    public function testAChangeNamesEachValueWithWhereItGoes(): void
    {
        $change = DataObjectChange::of(42)
            ->withValue('name', 'Chair')
            ->withLocalizedValue('description', 'en', 'A chair')
            ->withBrickValue('dimensions', 'Size', 'width', 40)
            ->withClassificationValue('attributes', 'Look', 'color', 'Red')
            ->withClassificationValue('attributes', 'Look', 'label', 'Rot', 'de')
            ->withValue('subtitle', null);

        self::assertSame(42, $change->id);
        self::assertNull($change->create);
        self::assertSame(
            [
                [FieldKind::Field, 'name', 'Chair', null, null, null, null, null],
                [FieldKind::Localized, 'description', 'A chair', 'en', null, null, null, null],
                [FieldKind::Brick, 'dimensions', 40, null, 'Size', 'width', null, null],
                [FieldKind::Classification, 'attributes', 'Red', 'default', null, null, 'Look', 'color'],
                [FieldKind::Classification, 'attributes', 'Rot', 'de', null, null, 'Look', 'label'],
                [FieldKind::Field, 'subtitle', null, null, null, null, null, null],
            ],
            array_map(
                static fn (FieldValue $v): array => [
                    $v->kind, $v->field, $v->value, $v->language, $v->brick, $v->brickField, $v->group, $v->key,
                ],
                $change->values()
            )
        );
    }

    public function testTheSamePlaceSetTwiceKeepsTheLaterValueWhereItWasFirstSet(): void
    {
        $change = DataObjectChange::of(1)
            ->withValue('name', 'First')
            ->withLocalizedValue('name', 'en', 'Localized')
            ->withValue('name', 'Second');

        self::assertSame(
            [[FieldKind::Field, 'Second'], [FieldKind::Localized, 'Localized']],
            array_map(static fn (FieldValue $v): array => [$v->kind, $v->value], $change->values())
        );
    }

    public function testEachWithReturnsANewChange(): void
    {
        $empty = DataObjectChange::of(1);
        $named = $empty->withValue('name', 'Chair');

        self::assertSame([], $empty->values());
        self::assertCount(1, $named->values());
    }

    public function testARelationKeepsItsTargetsById(): void
    {
        $targets = [ElementRef::object(7), ElementRef::asset(8), ElementRef::document(9)];

        self::assertSame($targets, DataObjectChange::of(1)->withValue('related', $targets)->values()[0]->value);
    }

    public function testANewObjectKeepsItsClassParentAndKey(): void
    {
        $change = DataObjectChange::create('chair-1', 'Product', 12, 'chair-1')->withValue('name', 'Chair');

        self::assertNull($change->id);
        self::assertEquals(new NewDataObject('chair-1', 'Product', 12, 'chair-1'), $change->create);
        self::assertCount(1, $change->values());
    }

    public function testAnExistingObjectHasAPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive id');
        DataObjectChange::of(0);
    }
}
