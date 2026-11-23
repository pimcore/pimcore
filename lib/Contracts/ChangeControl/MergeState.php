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
 * Where a merge stands. Running is under way; the others are final.
 *
 * The set is closed: no state is added in a minor release, so a consumer may match it exhaustively.
 * A merge that ended is not resumed through this contract.
 */
enum MergeState: string
{
    case Running = 'running';

    /** the check before writing found errors: nothing was written and the branch is open */
    case Refused = 'refused';

    /** every object was written or needed no write, and the branch is closed */
    case Merged = 'merged';

    /** a write failed: what was written stays and the branch is closed */
    case Partial = 'partial';

    public function isFinal(): bool
    {
        return $this !== self::Running;
    }
}
