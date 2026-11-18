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

/**
 * What a merge does with a value the branch changes that also changed on the object in the meantime.
 */
enum ConflictPolicy: string
{
    /** the merge's check fails: the conflict is a `MergeProblem` of a refused merge */
    case Stop = 'stop';

    /** the branch's value is written over the object's, as when a file being imported wins */
    case BranchWins = 'branch_wins';
}
