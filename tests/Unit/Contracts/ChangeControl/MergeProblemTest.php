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
use Pimcore\Contracts\ChangeControl\MergeProblem;
use Pimcore\Contracts\ChangeControl\MergeProblemCode;
use Pimcore\Contracts\ChangeControl\ObjectRef;
use Pimcore\Tests\Support\Test\TestCase;

class MergeProblemTest extends TestCase
{
    public function testKeepsWhatItConcernsAndIsNoConflictByDefault(): void
    {
        $object = ObjectRef::of(42);
        $problem = new MergeProblem($object, 'name', 'The value is too long.');

        self::assertSame($object, $problem->object);
        self::assertSame('name', $problem->field);
        self::assertSame('The value is too long.', $problem->reason);
        self::assertFalse($problem->conflict);
    }

    public function testMayConcernTheMergeAsAWhole(): void
    {
        $problem = new MergeProblem(null, null, 'No worker picked the merge up.');

        self::assertNull($problem->object);
        self::assertNull($problem->field);
    }

    public function testAConflictNamesItsObjectAndValue(): void
    {
        self::assertTrue((new MergeProblem(ObjectRef::of(42), 'name', 'Changed on the object too.', true))->conflict);
    }

    /**
     * @dataProvider conflictsWithoutAPlace
     */
    public function testRefusesAConflictWithoutItsPlace(?ObjectRef $object, ?string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A conflict names');
        new MergeProblem($object, $field, 'Changed on the object too.', true);
    }

    /**
     * @return array<string, array{ObjectRef|null, string|null}>
     */
    public static function conflictsWithoutAPlace(): array
    {
        return [
            'no object' => [null, 'name'],
            'no value' => [ObjectRef::of(42), null],
        ];
    }

    public function testRefusesAnEmptyField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('field');
        new MergeProblem(ObjectRef::of(42), '', 'The value is too long.');
    }

    public function testRefusesAnEmptyReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reason');
        new MergeProblem(ObjectRef::of(42), 'name', '');
    }

    public function testHasTheCodeOtherWhenItNamesNone(): void
    {
        self::assertSame(MergeProblemCode::Other, (new MergeProblem(ObjectRef::of(42), 'name', 'Something failed.'))->code);
    }

    public function testAConflictHasTheCodeConflictWhenItNamesNone(): void
    {
        self::assertSame(
            MergeProblemCode::Conflict,
            (new MergeProblem(ObjectRef::of(42), 'name', 'Changed on the object too.', true))->code
        );
    }

    /**
     * @dataProvider codesOfNoConflict
     */
    public function testKeepsTheCodeItNames(MergeProblemCode $code): void
    {
        $problem = new MergeProblem(ObjectRef::of(42), null, 'SQLSTATE[23000]: Integrity constraint violation', code: $code);

        self::assertSame($code, $problem->code);
        self::assertFalse($problem->conflict);
    }

    /**
     * @return array<string, array{MergeProblemCode}>
     */
    public static function codesOfNoConflict(): array
    {
        $codes = [];
        foreach (MergeProblemCode::cases() as $code) {
            if ($code !== MergeProblemCode::Conflict) {
                $codes[$code->value] = [$code];
            }
        }

        return $codes;
    }

    public function testKeepsTheCodeConflictOnAConflict(): void
    {
        $problem = new MergeProblem(ObjectRef::of(42), 'name', 'Changed on the object too.', true, MergeProblemCode::Conflict);

        self::assertSame(MergeProblemCode::Conflict, $problem->code);
        self::assertTrue($problem->conflict);
    }

    public function testMayHaveACodeForTheMergeAsAWhole(): void
    {
        self::assertSame(
            MergeProblemCode::Interrupted,
            (new MergeProblem(null, null, 'No worker picked the merge up.', code: MergeProblemCode::Interrupted))->code
        );
    }

    public function testRefusesTheCodeConflictOnWhatIsNoConflict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only a conflict');
        new MergeProblem(ObjectRef::of(42), 'name', 'Changed on the object too.', code: MergeProblemCode::Conflict);
    }

    public function testRefusesAnotherCodeOnAConflict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only a conflict');
        new MergeProblem(ObjectRef::of(42), 'name', 'Changed on the object too.', true, MergeProblemCode::InvalidValue);
    }

    public function testCodesAreStableStrings(): void
    {
        self::assertSame(
            [
                'conflict',
                'object_gone',
                'not_allowed',
                'invalid_value',
                'missing_new_object',
                'invalid_place',
                'write_failed',
                'interrupted',
                'other',
            ],
            array_map(static fn (MergeProblemCode $code): string => $code->value, MergeProblemCode::cases())
        );
    }
}
