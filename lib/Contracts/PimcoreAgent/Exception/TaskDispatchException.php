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
 * Raised by `AgentTaskServiceInterface::start()` / `continueTask()` when the underlying
 * dispatch step (transport to the execution backend) rejects the request or fails on
 * transport. The task row for the failed dispatch is marked terminal before this is thrown,
 * so the caller does not need to reconcile a half-committed state.
 */
class TaskDispatchException extends RuntimeException implements AgentTaskExceptionInterface
{
}
