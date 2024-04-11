<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\JobExecutionEngineBundle\Agent;

use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\Job;
use Throwable;

/**
 * @internal
 */
interface JobExecutionAgentInterface
{
    /**
     * Start new Job Run based on a job definition
     */
    public function startJobExecution(Job $job, ?int $ownerId): JobRun;

    /**
     * Continue execution when one step is finished. Normally not needed from the outside, only for internal purposes.
     */
    public function continueJobStepExecution(JobExecutionEngineMessageInterface $message): void;

    /**
     * Called when a step execution failed. Normally not needed from the outside, only for internal purposes.
     */
    public function jobExecutionFailed(
        JobExecutionEngineMessageInterface $message,
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
