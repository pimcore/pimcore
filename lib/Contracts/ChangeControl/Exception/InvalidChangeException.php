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

namespace Pimcore\Contracts\ChangeControl\Exception;

use Pimcore\Contracts\ChangeControl\ObjectRef;
use RuntimeException;
use Throwable;

/**
 * A change that cannot be recorded: it names an object, class, field, brick, group, key, language or
 * relation target that does not exist, or a value its place does not take. Nothing of the call that
 * brought it was recorded.
 */
class InvalidChangeException extends RuntimeException implements ChangeControlExceptionInterface
{
    /**
     * @param ObjectRef|null $object the object the change is for, when the implementation can tell
     */
    public function __construct(
        string $message,
        public readonly ?ObjectRef $object = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
