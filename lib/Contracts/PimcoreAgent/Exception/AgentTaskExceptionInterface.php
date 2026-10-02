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

use Throwable;

/**
 * Marker interface for every failure mode documented by `AgentTaskServiceInterface`. Consumers
 * catch this to react to any agent-task-related error without having to enumerate concrete
 * exception classes (or to depend on the concrete implementation for the taxonomy).
 */
interface AgentTaskExceptionInterface extends Throwable
{
}
