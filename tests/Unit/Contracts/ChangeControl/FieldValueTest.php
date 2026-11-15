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
use Pimcore\Contracts\ChangeControl\FieldValue;
use Pimcore\Tests\Support\Test\TestCase;

class FieldValueTest extends TestCase
{
    /**
     * @dataProvider invalidValues
     */
    public function testRefusesANameThatIsEmptyOrReserved(callable $build, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $build();
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public static function invalidValues(): array
    {
        return [
            'an empty field' => [static fn () => FieldValue::field(' ', 1), 'field needs a name'],
            'a reserved field' => [static fn () => FieldValue::field('__parent', 1), 'reserved'],
            'a reserved localized field' => [static fn () => FieldValue::localized('__parent', 'en', 1), 'reserved'],
            'an empty language' => [static fn () => FieldValue::localized('name', '', 1), 'language needs a name'],
            'a reserved brick field' => [static fn () => FieldValue::inBrick('__b', 'Size', 'width', 1), 'reserved'],
            'an empty brick' => [static fn () => FieldValue::inBrick('bricks', '', 'width', 1), 'brick needs a name'],
            'an empty brick\'s field' => [
                static fn () => FieldValue::inBrick('bricks', 'Size', '', 1),
                'field needs a name',
            ],
            'a reserved store field' => [
                static fn () => FieldValue::classification('__s', 'Look', 'color', 'default', 1),
                'reserved',
            ],
            'an empty group' => [
                static fn () => FieldValue::classification('store', '', 'color', 'default', 1),
                'group needs a name',
            ],
            'an empty key' => [
                static fn () => FieldValue::classification('store', 'Look', '', 'default', 1),
                'key needs a name',
            ],
            'an empty store language' => [
                static fn () => FieldValue::classification('store', 'Look', 'color', '', 1),
                'language needs a name',
            ],
            'an empty language in a brick' => [
                static fn () => FieldValue::inBrick('bricks', 'Size', 'label', 1, ''),
                'language needs a name',
            ],
        ];
    }

    public function testTheSamePlaceIsTheSameAndAnotherLanguageIsAnother(): void
    {
        self::assertSame(FieldValue::field('name', 1)->place(), FieldValue::field('name', 2)->place());
        self::assertNotSame(FieldValue::field('name', 1)->place(), FieldValue::localized('name', 'en', 1)->place());
        self::assertNotSame(
            FieldValue::localized('name', 'en', 1)->place(),
            FieldValue::localized('name', 'de', 1)->place()
        );
    }

    /**
     * @dataProvider otherPlaces
     */
    public function testEachPartOfAPlaceTellsItApart(FieldValue $one, FieldValue $same, FieldValue $other): void
    {
        self::assertSame($one->place(), $same->place());
        self::assertNotSame($one->place(), $other->place());
    }

    /**
     * @return array<string, array{FieldValue, FieldValue, FieldValue}>
     */
    public static function otherPlaces(): array
    {
        $brick = FieldValue::inBrick('bricks', 'Size', 'width', 1);
        $sameBrick = FieldValue::inBrick('bricks', 'Size', 'width', 2);
        $store = FieldValue::classification('store', 'Look', 'color', 'default', 1);
        $sameStore = FieldValue::classification('store', 'Look', 'color', 'default', 2);

        return [
            'another bricks field' => [$brick, $sameBrick, FieldValue::inBrick('other', 'Size', 'width', 1)],
            'another brick' => [$brick, $sameBrick, FieldValue::inBrick('bricks', 'Weight', 'width', 1)],
            'another field in the brick' => [$brick, $sameBrick, FieldValue::inBrick('bricks', 'Size', 'height', 1)],
            'a language in the brick' => [$brick, $sameBrick, FieldValue::inBrick('bricks', 'Size', 'width', 1, 'en')],
            'another language in the brick' => [
                FieldValue::inBrick('bricks', 'Size', 'label', 1, 'en'),
                FieldValue::inBrick('bricks', 'Size', 'label', 2, 'en'),
                FieldValue::inBrick('bricks', 'Size', 'label', 1, 'de'),
            ],
            'another store field' => [
                $store,
                $sameStore,
                FieldValue::classification('other', 'Look', 'color', 'default', 1),
            ],
            'another group' => [$store, $sameStore, FieldValue::classification('store', 'Other', 'color', 'default', 1)],
            'another key' => [$store, $sameStore, FieldValue::classification('store', 'Look', 'label', 'default', 1)],
            'another store language' => [$store, $sameStore, FieldValue::classification('store', 'Look', 'color', 'de', 1)],
        ];
    }
}
