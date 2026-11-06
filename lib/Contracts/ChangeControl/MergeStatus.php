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

namespace Pimcore\Contracts\ChangeControl;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * A merge as its consumer sees it: the counts to show progress and, once it is final, what was
 * written and what went wrong.
 */
final readonly class MergeStatus
{
    /**
     * @param int $total the objects the merge covers
     * @param int $done the objects written so far
     * @param list<MergeProblem> $problems why it was refused or ended as partial
     * @param list<ObjectRef> $written the objects written, a new one by `ObjectRef::created()`; known once the
     *                                 merge is final
     */
    private function __construct(
        public Uuid $branch,
        public MergeState $state,
        public int $total,
        public int $done,
        public array $problems,
        public array $written,
    ) {
    }

    public static function running(Uuid $branch, int $total, int $done): self
    {
        if ($total < 0 || $done < 0 || $done > $total) {
            throw new InvalidArgumentException(sprintf('%d of %d objects cannot be done.', $done, $total));
        }

        return new self($branch, MergeState::Running, $total, $done, [], []);
    }

    /**
     * @param list<MergeProblem> $problems every check error
     */
    public static function refused(Uuid $branch, int $total, array $problems): self
    {
        self::assertTotal($total, 0);
        self::assertProblems($problems);

        return new self($branch, MergeState::Refused, $total, 0, $problems, []);
    }

    /**
     * @param list<ObjectRef> $written
     */
    public static function merged(Uuid $branch, array $written): self
    {
        return new self($branch, MergeState::Merged, count($written), count($written), [], $written);
    }

    /**
     * @param list<ObjectRef> $written
     * @param list<MergeProblem> $problems
     */
    public static function partial(Uuid $branch, int $total, array $written, array $problems): self
    {
        self::assertTotal($total, count($written));
        self::assertProblems($problems);

        return new self($branch, MergeState::Partial, $total, count($written), $problems, $written);
    }

    private static function assertTotal(int $total, int $written): void
    {
        if ($total < $written) {
            throw new InvalidArgumentException(
                sprintf('%d written objects do not fit a total of %d.', $written, $total)
            );
        }
    }

    /**
     * @param list<MergeProblem> $problems
     */
    private static function assertProblems(array $problems): void
    {
        if ($problems === []) {
            throw new InvalidArgumentException('A refused or partial merge names what went wrong.');
        }
    }
}
