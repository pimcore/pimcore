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

namespace Pimcore\Console\Exception;

use RuntimeException;

/**
 * Thrown by Parallelization::runBeforeFirstCommand() when another process holds the
 * command's lock, and caught by ParallelizationBase::execute(), which ends the run
 * without processing any items. A command that overrides runBeforeFirstCommand() or execute()
 * and calls the trait's version itself may have to handle it.
 */
final class CommandAlreadyRunningException extends RuntimeException
{
}
