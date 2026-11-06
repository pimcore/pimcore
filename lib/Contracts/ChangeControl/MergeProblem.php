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

/**
 * Why a merge was refused or ended as partial.
 */
final readonly class MergeProblem
{
    /**
     * @param ObjectRef|null $object null when it concerns the merge as a whole
     * @param string|null $field the value it concerns, when it is about one, e.g. `name` or `localizedfields.en.title`
     * @param bool $conflict the object changed this value too, under `ConflictPolicy::Stop`
     */
    public function __construct(
        public ?ObjectRef $object,
        public ?string $field,
        public string $reason,
        public bool $conflict = false,
    ) {
        if ($field === '') {
            throw new InvalidArgumentException('"field" must be null or a non-empty string.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('"reason" must be a non-empty string.');
        }
        if ($conflict && ($object === null || $field === null)) {
            throw new InvalidArgumentException('A conflict names the object and the value it is on.');
        }
    }
}
