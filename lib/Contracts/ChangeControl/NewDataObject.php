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
 * A data object a `DataObjectChange` creates: its class, its parent and its key. Its token names it
 * within the branch, so a later change reaches the same new object and another new object can name it
 * as its parent.
 */
final readonly class NewDataObject
{
    /**
     * @param string $token the consumer's name for it within the branch: letters, digits, `.`, `_` and `-`, at most 80
     * @param string $className the name of its class
     * @param int|string $parent an existing object's id, or the token of another object the branch creates
     * @param string $key its key under the parent
     */
    public function __construct(
        public string $token,
        public string $className,
        public int|string $parent,
        public string $key,
    ) {
        ObjectRef::token($token);
        if (trim($className) === '') {
            throw new InvalidArgumentException('A new object needs a class name.');
        }
        if (is_int($parent)) {
            ObjectRef::of($parent);
        } else {
            ObjectRef::token($parent);
        }
        if ($parent === $token) {
            throw new InvalidArgumentException(sprintf('The new object "%s" cannot be its own parent.', $token));
        }
        if (trim($key) === '') {
            throw new InvalidArgumentException('A new object needs a key.');
        }
    }
}
