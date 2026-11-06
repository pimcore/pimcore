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
use Pimcore\Contracts\ChangeControl\Exception\BranchNotFoundException;
use Pimcore\Contracts\ChangeControl\Exception\BranchNotWritableException;
use Pimcore\Contracts\ChangeControl\Exception\InvalidChangeException;
use Pimcore\Contracts\ChangeControl\Exception\WriteRefusedException;
use Pimcore\Model\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Writing to data objects through a change control branch, for a consumer that also works without
 * change control, such as an import: open a branch, record changes on it, take objects out again,
 * then merge or discard it. Nothing reaches the objects before the merge.
 *
 * Core ships no implementation. Whether one is installed shows in the dependency: a consumer takes
 * `?BranchServiceInterface $branches = null` and writes directly when it stays null.
 *
 * `$context` is the consumer's own name for where its branches belong, such as
 * `my-bundle:import-42`. A branch belongs to the context it was opened in: a call naming another
 * context does not see it.
 *
 * Refusals of a call are `ChangeControlExceptionInterface`. What a merge finds out about the objects
 * is no exception but its `MergeStatus`.
 */
interface BranchServiceInterface
{
    /**
     * Open an empty branch.
     *
     * @return Uuid the branch
     *
     * @throws InvalidArgumentException an empty name or context
     */
    public function open(string $name, string $context, UserInterface $by): Uuid;

    /**
     * Record the changes on the branch, each over what the branch already holds for its object: a
     * value it names replaces the one recorded before, every other one stays. Every change is
     * checked before any is recorded.
     *
     * @param list<DataObjectChange> $changes
     *
     * @throws BranchNotFoundException    there is no such branch, or it belongs to another context
     * @throws BranchNotWritableException the branch is closed or being merged
     * @throws WriteRefusedException      this user may not write to the branch
     * @throws InvalidChangeException     a change names what does not exist, or a value its place does not take
     */
    public function record(Uuid $branch, string $context, UserInterface $by, array $changes): void;

    /**
     * Take objects out of the branch again, with everything recorded for them. What the branch does
     * not hold is skipped, so a second call changes nothing. An object recorded with a taken out new
     * object as its parent stays on the branch, and the merge's check then names it.
     *
     * @param list<ObjectRef> $objects an existing object by its id, a new one by its token
     *
     * @throws BranchNotFoundException    there is no such branch, or it belongs to another context
     * @throws BranchNotWritableException the branch is closed or being merged
     * @throws WriteRefusedException      this user may not write to the branch
     */
    public function takeOut(Uuid $branch, string $context, UserInterface $by, array $objects): void;

    /**
     * Start merging the branch, as this user: the rights on the objects are checked for them. The
     * returned status may still be running; follow it with `mergeStatus()`.
     *
     * Every object is checked before anything is written; one that no longer exists fails the check.
     * Any check error ends the merge as `MergeState::Refused`, naming every object that failed:
     * nothing was written and the branch is open, so the consumer can take those objects out and
     * merge again, or discard the branch.
     *
     * An object that already holds what the branch recorded, and whose published state the mode does
     * not change, needs no write: the merge leaves it out and its status names it. With
     * `MergeMode::Publish` an unpublished one is still written, to publish it.
     *
     * A write that fails after that ends the merge as `MergeState::Partial`: what was written stays,
     * the branch is closed and the status names both. A branch that holds nothing merges with a
     * total of 0.
     *
     * @throws BranchNotFoundException    there is no such branch, or it belongs to another context
     * @throws BranchNotWritableException the branch is closed or being merged
     * @throws WriteRefusedException      this user may not merge the branch
     */
    public function merge(
        Uuid $branch,
        string $context,
        UserInterface $by,
        MergeMode $mode,
        ConflictPolicy $conflicts,
    ): MergeStatus;

    /**
     * The branch's latest merge. Reading it takes no right on the branch, only its context, and this
     * method throws no refusal.
     *
     * A merge no one drives any more is ended here, as this user: as partial, or as refused when it
     * wrote nothing. While that cannot be done (this user may not, or an object is still being
     * written) it reads as running and a later call ends it, so a consumer that polls needs a limit
     * of its own.
     *
     * @return MergeStatus|null null when the branch never had one, there is no such branch, or it
     *                          belongs to another context
     */
    public function mergeStatus(Uuid $branch, string $context, UserInterface $by): ?MergeStatus;

    /**
     * Close the branch without merging it. A branch that is closed already stays as it is, so this
     * is safe to call more than once.
     *
     * @throws BranchNotFoundException    there is no such branch, or it belongs to another context
     * @throws BranchNotWritableException the branch is being merged
     * @throws WriteRefusedException      this user may not write to the branch
     */
    public function discard(Uuid $branch, string $context, UserInterface $by): void;
}
