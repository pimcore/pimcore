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
 * Raised by `AgentTaskServiceInterface::start()` / `continueTask()` when the implementation's
 * concurrency cap for running tasks is already reached — a continuation starts a new running
 * task, so it is counted by and must respect the same cap. Whether the cap is enforced with a
 * lock or best-effort is an implementation detail; the exception is the same either way.
 */
class TooManyTasksException extends RuntimeException implements AgentTaskExceptionInterface
{
}
