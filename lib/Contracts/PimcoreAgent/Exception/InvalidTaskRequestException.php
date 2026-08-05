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

namespace Pimcore\Contracts\PimcoreAgent\Exception;

use RuntimeException;

/**
 * Raised by `AgentTaskServiceInterface::start()` / `continueTask()` when an
 * `AgentTaskRequest` fails fail-closed validation — an unknown agent configuration, no
 * identity the task can act as (neither a configured execution user nor a caller-supplied
 * `actingUserId`), or a resume attempted against a task that is not yet terminal.
 */
class InvalidTaskRequestException extends RuntimeException implements AgentTaskExceptionInterface
{
}
