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

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\JobExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @internal
 */
final class JobExecutionAgent implements JobExecutionAgentInterface
{
    private bool $isDev;

    public function __construct(
        string $environment,
        private readonly MessageBusInterface $jobExecutionEngineBus,
        private readonly JobRunRepositoryInterface $jobRunRepository,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator
    ) {
        $this->isDev = $environment === 'dev';
    }

    /**
     * @throws Exception
     */
    public function startJobExecution(Job $job, ?int $ownerId): JobRun
    {
        $jobRun = $this->jobRunRepository->createFromJob($job, $ownerId);

        if (count($job->getSteps()) === 0) {
            $this->setJobRunError(
                $jobRun,
                'pimcore_job_execution_engine_error_no_steps',
                ['%job_name%' => $job->getName()]
            );

            return $jobRun;
        }

        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);

        $this->jobRunRepository->update($jobRun);

        $this->dispatchStepMessage($jobRun);

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    public function continueJobStepExecution(JobExecutionEngineMessageInterface $message): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'pimcore_job_execution_engine_error_no_job_definition',
                ['%job_run_id%' => $jobRun->getId()]
            );

            return;
        }

        $this->logger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Job step {$jobRun->getCurrentStep()} of Job '{$job->getName()}' finished."
        );

        if ($jobRun->getState() === JobRunStates::CANCELLED) {
            $this->logger->info(
                "[JobRun {$jobRun->getId()}]: Cancel stop execution due to JobRun was cancelled."
            );

            return;
        }

        $nextStep = $jobRun->getCurrentStep() + 1;

        if (count($job->getSteps()) <= $nextStep) {
            $jobRun->setCurrentStep(null);
            $jobRun->setCurrentMessage(null);
            $jobRun->setState(JobRunStates::FINISHED);
            $this->jobRunRepository->update($jobRun);

            $this->logger->info("[JobRun {$jobRun->getId()}]: Job '{$job->getName()}' finished.");
        } else {
            $jobRun->setCurrentStep($nextStep);
            $this->jobRunRepository->update($jobRun);
            $this->dispatchStepMessage($jobRun);
        }
    }

    /**
     * @throws Exception
     */
    public function jobExecutionFailed(JobExecutionEngineMessageInterface $message, ?Throwable $throwable = null): void
    {

        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());
        if ($throwable) {
            $this->logger->error("[JobRun {$jobRun->getId()}]: " . $throwable);
        }

        $errorMessage =  'Unknown Error.';

        if ($throwable) {
            $errorMessage = $throwable->getMessage();

            if ($this->isDev) {
                $errorMessage .= ' Stack trace: ' . str_replace("\n", '', $throwable->getTraceAsString());
            }
        }

        $this->setJobRunError($jobRun, $errorMessage, [], false);
    }

    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);

        return $ownerId === $jobRun->getOwnerId();
    }

    /**
     * @throws Exception
     */
    public function cancelJobRun(int $jobRunId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        $this->logger->info("[JobRun {$jobRun->getId()}]: JobRun cancelled.");
        $jobRun->setState(JobRunStates::CANCELLED);
        $this->jobRunRepository->updateLogLocalized(
            $jobRun, 'pimcore_job_execution_engine_job_cancelled', ['%job_run_id%' => $jobRun->getId()]
        );
    }

    /**
     * @throws Exception
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId);
        $this->startJobExecution($jobRun->getJob(), $ownerId);
    }

    /**
     * @throws Exception
     */
    protected function setJobRunError(
        JobRun $jobRun,
        string $errorMessage,
        array $params = [],
        bool $translate = true
    ): void {
        $jobRun->setState(JobRunStates::FAILED);

        if ($translate) {
            $translatedMessage = $this->translator->trans($errorMessage, $params);
            $this->logger->info(
                "[JobRun {$jobRun->getId()}]: " . $translatedMessage . ' --> Job execution failed.'
            );
            $this->jobRunRepository->updateLogLocalized($jobRun, $errorMessage, $params);
        } else {
            $this->logger->info(
                "[JobRun {$jobRun->getId()}]: " . $errorMessage . ' --> Job execution failed.'
            );
            $jobRun->setCurrentMessage($errorMessage);
            $this->jobRunRepository->update($jobRun);
            $this->jobRunRepository->updateLog($jobRun, $errorMessage);
        }
    }

    /**
     * @throws Exception
     */
    protected function dispatchStepMessage(JobRun $jobRun): void
    {
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'pimcore_job_execution_engine_error_no_job_definition',
                ['%job_id%' => $jobRun->getId()]
            );

            return;
        }

        $this->logger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Starting job step {$jobRun->getCurrentStep()} of Job '{$job->getName()}'."
        );

        $jobStep = $job->getSteps()[$jobRun->getCurrentStep()];
        $messageString = $jobStep->getMessageFQCN();

        if (!class_exists($messageString)) {
            $this->setJobRunError(
                $jobRun,
                'pimcore_job_execution_engine_error_missing_message_implementation',
                ['%message%' => $messageString, '%job_name%' => $job->getName()]
            );

            return;
        }

        $this->jobExecutionEngineBus->dispatch(new $messageString($jobRun->getId(), $jobRun->getCurrentStep()));
    }

    public function isRunning(int $jobRunId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);

        return $jobRun->getState() === JobRunStates::RUNNING;
    }
}
