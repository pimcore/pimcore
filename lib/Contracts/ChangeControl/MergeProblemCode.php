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
 * The kind of a `MergeProblem`, so a consumer can word it itself instead of showing its reason.
 *
 * More kinds may follow in a minor release: a consumer handles one it does not know as `Other`.
 */
enum MergeProblemCode: string
{
    /** the object changed this value too, under `ConflictPolicy::Stop` */
    case Conflict = 'conflict';

    /** the object no longer exists */
    case ObjectGone = 'object_gone';

    /** the merging user may not write the object, or not as the mode asks */
    case NotAllowed = 'not_allowed';

    /** saving the object would refuse a value, e.g. a mandatory one left empty or one too long */
    case InvalidValue = 'invalid_value';

    /** it points at a new object the branch does not create, e.g. one taken out, as its parent or in a value */
    case MissingNewObject = 'missing_new_object';

    /** a new object cannot be created where it was recorded: its parent cannot hold it, or another object takes its key there */
    case InvalidPlace = 'invalid_place';

    /** writing the object failed after the check had passed */
    case WriteFailed = 'write_failed';

    /** the merge ended before it got to the object, or to anything, e.g. as no one drove it any more */
    case Interrupted = 'interrupted';

    /** none of the others: only the reason says what went wrong */
    case Other = 'other';
}
