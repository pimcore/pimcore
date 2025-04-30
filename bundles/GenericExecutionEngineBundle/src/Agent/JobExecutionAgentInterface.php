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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Agent;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Throwable;

interface JobExecutionAgentInterface
{
    /**
     * Start new Job Run based on a job definition
     */
    public function startJobExecution(
        Job $job,
        ?int $ownerId,
        string $executionContext = 'default'
    ): JobRun;

    /**
     * Continue execution when a message is finished.
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable $throwable = null
    ): void;

    /**
     * checks if interaction with job run is allowed by given user
     */
    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool;

    /**
     * Cancel given job run
     */
    public function cancelJobRun(int $jobRunId): void;

    /**
     * Start new job based on given job run
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void;

    /**
     * Checks if job run is running
     */
    public function isRunning(int $jobRunId): bool;
}
