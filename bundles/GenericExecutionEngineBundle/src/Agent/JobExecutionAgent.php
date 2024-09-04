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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Agent;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Helper\StopMessengerWorkersTrait;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @internal
 */
final class JobExecutionAgent implements JobExecutionAgentInterface
{
    use StopMessengerWorkersTrait;

    private const LOG_JOB_RUN_ID_KEY = '%job_run_id%';

    private const LOG_JOB_RUN_NAME_KEY = '%job_run_name%';

    private bool $isDev;

    public function __construct(
        string $environment,
        private readonly string $errorHandlingMode,
        private readonly ExecutionContextInterface $executionContext,
        private readonly JobRunRepositoryInterface $jobRunRepository,
        private readonly JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private readonly LoggerInterface $genericExecutionEngineLogger,
        private readonly MessageBusInterface $executionEngineBus,
        private readonly Translator $translator
    ) {
        $this->isDev = $environment === 'dev';
    }

    /**
     * @throws Exception
     */
    public function startJobExecution(
        Job $job,
        ?int $ownerId,
        string $executionContext = 'default'
    ): JobRun {
        $jobRun = $this->jobRunRepository->createFromJob($job, $ownerId);
        $jobRun->setExecutionContext($executionContext);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(count($job->getSelectedElements()));

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_started',
            $this->getLogParams($jobRun)
        );
        $this->jobRunRepository->update($jobRun);

        $this->dispatchStepMessage($jobRun);

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable $throwable = null
    ): void {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());
        if (!$this->isRunning($jobRun->getId())) {
            return;
        }

        if ($jobRun->getTotalElements() > 0) {
            $this->incrementProcessedElements($jobRun);
        }

        if (!$throwable) {
            $this->handleNextMessage($message);

            return;
        }

        $this->handleJobExecutionError($message, $throwable);
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
        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: JobRun cancelled.");
        $jobRun->setState(JobRunStates::CANCELLED);
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_cancelled',
            $this->getLogParams($jobRun)
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

    public function isRunning(int $jobRunId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);

        return $jobRun->getState() === JobRunStates::RUNNING;
    }

    private function getSelectionProcessingModeFromJobRun(JobRun $jobRun): SelectionProcessingMode
    {
        $steps = $jobRun->getJob()?->getSteps();
        if ($steps !== null) {
            $step = $steps[$jobRun->getCurrentStep()] ?? null;
            if ($step) {
                return $step->getSelectionProcessingMode();
            }
        }

        return SelectionProcessingMode::FOR_EACH;
    }

    /**
     * @throws Exception
     */
    private function handleNextMessage(GenericExecutionEngineMessageInterface $message): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());

        if ($this->getSelectionProcessingModeFromJobRun($jobRun) === SelectionProcessingMode::ONCE ||
            $jobRun->getProcessedElementsForStep() === $jobRun->getTotalElements()) {
            $this->continueJobStepExecution($message);
        }
    }

    /**
     * @throws Exception
     */
    private function continueJobStepExecution(GenericExecutionEngineMessageInterface $message): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_no_job_definition',
                $this->getLogParams($jobRun)
            );

            return;
        }

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Job step {$jobRun->getCurrentStep()} of Job '{$job->getName()}' finished."
        );

        if ($jobRun->getState() === JobRunStates::CANCELLED) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: Cancel stop execution due to JobRun was cancelled."
            );

            return;
        }

        $nextStep = $jobRun->getCurrentStep() + 1;

        if (count($job->getSteps()) <= $nextStep) {
            $jobRun->setCurrentStep(null);
            $this->setCompletionState($jobRun);
            $this->jobRunRepository->update($jobRun);

            $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: Job '{$job->getName()}' finished.");
        } else {
            $jobRun->setProcessedElementsForStep(0);
            $jobRun->setCurrentStep($nextStep);
            $this->jobRunRepository->update($jobRun);
            $this->dispatchStepMessage($jobRun);
        }
    }

    /**
     * @throws Exception
     */
    private function handleJobExecutionError(
        GenericExecutionEngineMessageInterface $message,
        Throwable $throwable
    ): void {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId());

        $this->genericExecutionEngineLogger->error("[JobRun {$jobRun->getId()}]: " . $throwable);
        $errorMessage = $throwable->getMessage();
        if ($this->isDev) {
            $errorMessage .= ' Stack trace: ' . str_replace("\n", '', $throwable->getTraceAsString());
        }

        $this->jobRunErrorLogRepository->createFromJobRun(
            $jobRun,
            $message->getElement()?->getId(),
            $errorMessage,
        );

        match
        (
            $this->getErrorHandlingMode(
                $jobRun,
                $this->getSelectionProcessingModeFromJobRun($jobRun)
            )
        ) {
            ErrorHandlingMode::STOP_ON_FIRST_ERROR =>
            $this->stopJobExecutionOnError(
                $jobRun,
                $errorMessage
            ),
            ErrorHandlingMode::CONTINUE_ON_ERROR =>
            $this->continueJobExecutionOnError(
                $jobRun,
                $message,
                $errorMessage
            )
        };
    }

    private function getErrorHandlingMode(
        JobRun $jobRun,
        SelectionProcessingMode $selectionProcessingMode
    ): ErrorHandlingMode {
        if ($selectionProcessingMode === SelectionProcessingMode::ONCE) {
            return ErrorHandlingMode::STOP_ON_FIRST_ERROR;
        }

        $errorHandling = $this->executionContext->getErrorHandlingFromContext($jobRun->getExecutionContext());
        if ($errorHandling === null) {
            $errorHandling = $this->errorHandlingMode;
        }

        return ErrorHandlingMode::from($errorHandling);
    }

    /**
     * @throws Exception
     */
    private function continueJobExecutionOnError(
        JobRun $jobRun,
        GenericExecutionEngineMessageInterface $message,
        string $errorMessage
    ): void {
        $this->setJobRunError(
            $jobRun,
            $errorMessage,
            [],
            false,
            JobRunStates::RUNNING
        );

        $this->handleNextMessage($message);
    }

    /**
     * @throws Exception
     */
    private function stopJobExecutionOnError(
        JobRun $jobRun,
        string $errorMessage
    ): void {
        $this->stopMessengerWorkers();
        $this->setJobRunError($jobRun, $errorMessage, [], false);
        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: JobRun cancelled due to errors.");
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_failed',
            $this->getLogParams($jobRun)
        );
    }

    private function incrementProcessedElements(JobRun $jobRun): void
    {
        $currentElementCount = $jobRun->getProcessedElementsForStep() + 1;

        $jobRun->setProcessedElementsForStep($currentElementCount);
        $this->jobRunRepository->update($jobRun);
    }

    /**
     * @throws Exception
     */
    private function setJobRunError(
        JobRun $jobRun,
        string $errorMessage,
        array $params = [],
        bool $translate = true,
        ?JobRunStates $status = null
    ): void {
        $jobRun->setState($status ?? JobRunStates::FAILED);
        if ($translate) {
            $translatedMessage = $this->translator->trans($errorMessage, $params);
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: " . $translatedMessage . ' --> Job execution failed.'
            );
            $this->jobRunRepository->updateLogLocalizedWithDomain(
                $jobRun,
                $errorMessage,
                $params
            );
        } else {
            $this->genericExecutionEngineLogger->info(
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
    private function dispatchStepMessage(JobRun $jobRun): void
    {
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_no_job_definition',
                ['%job_id%' => $jobRun->getId()]
            );

            return;
        }

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Starting job step {$jobRun->getCurrentStep()} of Job '{$job->getName()}'."
        );

        $jobStep = $job->getSteps()[$jobRun->getCurrentStep()];
        $messageString = $jobStep->getMessageFQCN();

        if (!class_exists($messageString)) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_missing_message_implementation',
                ['%message%' => $messageString, '%job_run_name%' => $job->getName()]
            );

            return;
        }

        $this->dispatchSelectedElements(
            $jobRun->getId(),
            $jobRun->getCurrentStep(),
            $messageString,
            $this->getSelectionProcessingModeFromJobRun($jobRun),
            $job->getSelectedElements()
        );
    }

    private function getLogParams(JobRun $jobRun): array
    {
        return [
            self::LOG_JOB_RUN_ID_KEY => $jobRun->getId(),
            self::LOG_JOB_RUN_NAME_KEY => $jobRun->getJob()?->getName(),
        ];
    }

    /**
     * @throws Exception
     */
    private function setCompletionState(JobRun $jobRun): void
    {
        $message = '';

        $logs = $this->jobRunErrorLogRepository->getLogsByJobRunId(
            $jobRun->getId(),
            $jobRun->getCurrentStep()
        );

        if (count($logs) === $jobRun->getTotalElements()) {
            $jobRun->setState(JobRunStates::FAILED);
            $message = 'gee_job_failed';
        } elseif (count($logs) > 0) {
            $jobRun->setState(JobRunStates::FINISHED_WITH_ERRORS);
            $message = 'gee_job_finished_with_errors';
        }

        if (empty($logs)) {
            $jobRun->setCurrentMessage(null);
            $jobRun->setState(JobRunStates::FINISHED);
            $message = 'gee_job_finished';
        }

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            $message,
            $this->getLogParams($jobRun)
        );
    }

    /**
     * @param ElementDescriptor[] $selectedElements
     */
    private function dispatchSelectedElements(
        int $jobRunId,
        int $currentStepId,
        string $messageString,
        SelectionProcessingMode $selectionProcessingMode,
        array $selectedElements = []
    ): void {
        if (empty($selectedElements) || $selectionProcessingMode === SelectionProcessingMode::ONCE) {
            $this->executionEngineBus->dispatch(new $messageString(
                $jobRunId,
                $currentStepId
            )
            );

            return;
        }

        foreach ($selectedElements as $selectedElement) {
            $this->executionEngineBus->dispatch(new $messageString(
                $jobRunId,
                $currentStepId,
                $selectedElement
            )
            );
        }
    }
}
