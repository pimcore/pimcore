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
 * Raised by `AgentTaskServiceInterface::waitFor()` when its timeout elapses before the task
 * reaches a terminal state. The task itself is unaffected and keeps running — timing out only
 * means the caller abandoned the wait. Call `AgentTaskServiceInterface::cancel()` explicitly
 * if the abandonment should also kill the run.
 */
class TaskWaitTimeoutException extends RuntimeException implements AgentTaskExceptionInterface
{
}
