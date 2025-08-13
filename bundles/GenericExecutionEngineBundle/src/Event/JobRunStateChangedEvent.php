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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class JobRunStateChangedEvent extends Event
{
    public function __construct(
        private readonly int $jobRunId,
        private readonly string $jobName,
        private readonly int $jobRunOwnerId,
        private readonly string $oldState,
        private readonly string $newState
    ) {

    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getJobRunOwnerId(): int
    {
        return $this->jobRunOwnerId;
    }

    public function getOldState(): string
    {
        return $this->oldState;
    }

    public function getNewState(): string
    {
        return $this->newState;
    }
}
