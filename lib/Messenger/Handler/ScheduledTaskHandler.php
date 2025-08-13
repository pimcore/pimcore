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

namespace Pimcore\Messenger\Handler;

use Pimcore\Maintenance\ExecutorInterface;
use Pimcore\Messenger\ScheduledTaskMessage;

/**
 * @internal
 */
class ScheduledTaskHandler
{
    public function __construct(
        private ExecutorInterface $maintenanceExecutor
    ) {
    }

    public function __invoke(ScheduledTaskMessage $message): void
    {
        $this->maintenanceExecutor->executeTask($message->getName());
    }
}
