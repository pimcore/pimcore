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
use Pimcore\Contracts\ChangeControl\MergeState;
use Pimcore\Contracts\ChangeControl\MergeStatus;
use Pimcore\Contracts\ChangeControl\ObjectRef;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Uid\Uuid;

class MergeStatusTest extends TestCase
{
    private Uuid $branch;

    private MergeProblem $problem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Uuid::v7();
        $this->problem = new MergeProblem(ObjectRef::of(42), 'name', 'The value is too long.');
    }

    public function testARunningMergeShowsItsProgressAndNothingFinal(): void
    {
        $status = MergeStatus::running($this->branch, 10, 4);

        self::assertSame($this->branch, $status->branch);
        self::assertSame(MergeState::Running, $status->state);
        self::assertFalse($status->state->isFinal());
        self::assertSame([10, 4], [$status->total, $status->done]);
        self::assertSame([], $status->problems);
        self::assertSame([], $status->written);
        self::assertSame([], $status->leftOut);
    }

    /**
     * @dataProvider impossibleProgress
     */
    public function testRefusesImpossibleProgress(int $total, int $done): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be done');
        MergeStatus::running($this->branch, $total, $done);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function impossibleProgress(): array
    {
        return [
            'a negative total' => [-1, 0],
            'negative progress' => [1, -1],
            'more done than there is' => [1, 2],
        ];
    }

    public function testARefusedMergeNamesItsErrorsAndWroteNothing(): void
    {
        $status = MergeStatus::refused($this->branch, 10, [$this->problem]);

        self::assertSame(MergeState::Refused, $status->state);
        self::assertTrue($status->state->isFinal());
        self::assertSame([10, 0], [$status->total, $status->done]);
        self::assertSame([$this->problem], $status->problems);
        self::assertSame([], $status->written);
    }

    public function testARefusedMergeCannotGoWithoutAnError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('names what went wrong');
        MergeStatus::refused($this->branch, 10, []);
    }

    public function testARefusedMergeCannotHaveANegativeTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not fit');
        MergeStatus::refused($this->branch, -1, [$this->problem]);
    }

    public function testAMergedBranchNamesWhatWasWrittenAndWhichIdANewObjectGot(): void
    {
        $written = [ObjectRef::of(42), ObjectRef::created('sku-1', 43)];
        $status = MergeStatus::merged($this->branch, $written);

        self::assertSame(MergeState::Merged, $status->state);
        self::assertTrue($status->state->isFinal());
        self::assertSame([2, 2], [$status->total, $status->done]);
        self::assertSame($written, $status->written);
        self::assertSame(43, $status->written[1]->id);
        self::assertSame([], $status->problems);
    }

    public function testAMergedBranchKeepsWhatNeededNoWriteInItsTotalAndNamesIt(): void
    {
        $written = [ObjectRef::of(41), ObjectRef::created('sku-1', 43)];
        $leftOut = [ObjectRef::of(42)];
        $status = MergeStatus::merged($this->branch, $written, $leftOut);

        self::assertSame(MergeState::Merged, $status->state);
        self::assertSame([3, 2], [$status->total, $status->done]);
        self::assertSame($written, $status->written);
        self::assertSame($leftOut, $status->leftOut);
    }

    public function testABranchThatHoldsNothingMergesWithATotalOfZero(): void
    {
        $status = MergeStatus::merged($this->branch, []);

        self::assertSame(MergeState::Merged, $status->state);
        self::assertSame([0, 0], [$status->total, $status->done]);
    }

    public function testAPartialMergeNamesWhatWasWrittenAndWhatFailed(): void
    {
        $written = [ObjectRef::of(41)];
        $status = MergeStatus::partial($this->branch, 10, $written, [$this->problem]);

        self::assertSame(MergeState::Partial, $status->state);
        self::assertTrue($status->state->isFinal());
        self::assertSame([10, 1], [$status->total, $status->done]);
        self::assertSame($written, $status->written);
        self::assertSame([$this->problem], $status->problems);
    }

    public function testAPartialMergeMayHaveWrittenEveryObjectWhenAStepAfterTheWriteFailed(): void
    {
        $status = MergeStatus::partial($this->branch, 1, [ObjectRef::of(41)], [$this->problem]);

        self::assertSame([1, 1], [$status->total, $status->done]);
    }

    public function testAPartialMergeNamesWhatNeededNoWrite(): void
    {
        $leftOut = [ObjectRef::of(42)];
        $status = MergeStatus::partial($this->branch, 10, [ObjectRef::of(41)], [$this->problem], $leftOut);

        self::assertSame([10, 1], [$status->total, $status->done]);
        self::assertSame($leftOut, $status->leftOut);
    }

    public function testAPartialMergeCannotLeaveOutMoreThanFitsItsTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not fit');
        MergeStatus::partial($this->branch, 1, [ObjectRef::of(41)], [$this->problem], [ObjectRef::of(42)]);
    }

    public function testAPartialMergeCannotGoWithoutAnError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('names what went wrong');
        MergeStatus::partial($this->branch, 10, [ObjectRef::of(41)], []);
    }

    public function testAPartialMergeCannotHaveWrittenMoreThanItCovers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not fit');
        MergeStatus::partial($this->branch, 1, [ObjectRef::of(41), ObjectRef::of(42)], [$this->problem]);
    }
}
