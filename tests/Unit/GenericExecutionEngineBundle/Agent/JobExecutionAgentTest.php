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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Agent;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\AbstractExecutionEngineMessage;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunElementCounterInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\GenericExecutionEngineBundle\Agent\Support\ProcessElementMessage;
use Pimcore\Translation\Translator;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class JobExecutionAgentTest extends TestCase
{
    private const JOB_RUN_ID = 1;

    /**
     * Elements of one job step are processed by several workers in parallel. Each worker holds
     * its own copy of the job run, so the processed elements must be counted atomically in the
     * database instead of on the entity, otherwise the step never reaches its element count.
     */
    public function testJobRunFinishesWhenWorkersHoldStaleProcessedElementCounts(): void
    {
        $elements = [new ElementDescriptor('object', 1), new ElementDescriptor('object', 2)];
        $jobRun = $this->createRunningJobRun($elements);

        $processedElementsInDatabase = 0;
        $counter = $this->createMock(JobRunElementCounterInterface::class);
        $counter->method('incrementProcessedElementsForStep')->willReturnCallback(
            static function (JobRun $jobRun) use (&$processedElementsInDatabase): int {
                $jobRun->setProcessedElementsForStep(++$processedElementsInDatabase);

                return $processedElementsInDatabase;
            }
        );
        $agent = $this->createAgent($this->createRepository($jobRun), $counter);

        $agent->continueJobMessageExecution($this->createMessage($elements[0]));
        $this->assertSame(JobRunStates::RUNNING, $jobRun->getState());

        // the second element is processed by a worker whose copy of the job run
        // has not seen the first increment
        $jobRun->setProcessedElementsForStep(0);
        $agent->continueJobMessageExecution($this->createMessage($elements[1]));

        $this->assertSame(JobRunStates::FINISHED, $jobRun->getState());
    }

    public function testJobRunKeepsRunningUntilAllElementsAreProcessed(): void
    {
        $elements = [new ElementDescriptor('object', 1), new ElementDescriptor('object', 2)];
        $jobRun = $this->createRunningJobRun($elements);

        $counter = $this->createMock(JobRunElementCounterInterface::class);
        $counter->method('incrementProcessedElementsForStep')->willReturn(1);
        $agent = $this->createAgent($this->createRepository($jobRun), $counter);

        $agent->continueJobMessageExecution($this->createMessage($elements[0]));

        $this->assertSame(JobRunStates::RUNNING, $jobRun->getState());
    }

    /**
     * The error bookkeeping of a failed element flushes the job run. Once the final element count
     * is reached, another worker may complete the job run, so the bookkeeping must be persisted
     * before the element is counted, otherwise it could flush a completed job run back to running.
     */
    public function testErrorBookkeepingIsPersistedBeforeTheFailedElementIsCounted(): void
    {
        $elements = [new ElementDescriptor('object', 1), new ElementDescriptor('object', 2)];
        $jobRun = $this->createRunningJobRun($elements);

        $calls = [];
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->method('getJobRunById')->willReturn($jobRun);
        $repository->method('update')->willReturnCallback(
            static function (JobRun $jobRun) use (&$calls): JobRun {
                $calls[] = 'update';

                return $jobRun;
            }
        );
        $counter = $this->createMock(JobRunElementCounterInterface::class);
        $counter->method('incrementProcessedElementsForStep')->willReturnCallback(
            static function () use (&$calls): int {
                $calls[] = 'increment';

                return 2;
            }
        );
        $errorLogRepository = $this->createMock(JobRunErrorLogRepositoryInterface::class);
        $errorLogRepository->method('getTotalCountByJobRunId')->willReturn(1);
        $agent = $this->createAgent($repository, $counter, $errorLogRepository);

        $agent->continueJobMessageExecution(
            $this->createMessage($elements[1]),
            new RuntimeException('element failed')
        );

        $this->assertContains('update', $calls);
        $this->assertGreaterThan(
            array_search('update', $calls, true),
            array_search('increment', $calls, true),
            'The job run must be flushed before the element is counted'
        );
        $this->assertSame(JobRunStates::FINISHED_WITH_ERRORS, $jobRun->getState());
    }

    /**
     * A step is marked as failed as soon as one of its elements fails, so a run in which only some
     * elements failed must be told apart from a run in which all of them failed. The outcome must not
     * depend on the order in which the elements are processed.
     *
     * @dataProvider partiallyFailedSingleStepJobProvider
     *
     * @param int[] $failingPositions
     */
    public function testSingleStepJobWithSomeFailedElementsFinishesWithErrors(array $failingPositions): void
    {
        $jobRun = $this->runJob(1, 5, [0 => $failingPositions]);

        $this->assertSame(JobRunStates::FINISHED_WITH_ERRORS, $jobRun->getState());
    }

    /**
     * @return array<string, array{int[]}>
     */
    public static function partiallyFailedSingleStepJobProvider(): array
    {
        return [
            'first element fails' => [[0]],
            'element in the middle fails' => [[2]],
            'last element fails' => [[4]],
            'all but one element fail' => [[0, 1, 2, 3]],
        ];
    }

    public function testSingleStepJobWithoutFailedElementsFinishes(): void
    {
        $jobRun = $this->runJob(1, 5, []);

        $this->assertSame(JobRunStates::FINISHED, $jobRun->getState());
    }

    public function testSingleStepJobFailsWhenEveryElementFails(): void
    {
        $jobRun = $this->runJob(1, 5, [0 => [0, 1, 2, 3, 4]]);

        $this->assertSame(JobRunStates::FAILED, $jobRun->getState());
    }

    public function testSingleStepJobWithoutSelectedElementsFailsWhenItsMessageFails(): void
    {
        $jobRun = $this->runJob(1, 0, [0 => [null]]);

        $this->assertSame(JobRunStates::FAILED, $jobRun->getState());
    }

    public function testMultiStepJobWithSomeFailedElementsInEveryStepFinishesWithErrors(): void
    {
        $jobRun = $this->runJob(2, 5, [0 => [1], 1 => [3]]);

        $this->assertSame(JobRunStates::FINISHED_WITH_ERRORS, $jobRun->getState());
    }

    public function testMultiStepJobWithOneEntirelyFailedStepFinishesWithErrors(): void
    {
        $jobRun = $this->runJob(2, 5, [0 => [0, 1, 2, 3, 4]]);

        $this->assertSame(JobRunStates::FINISHED_WITH_ERRORS, $jobRun->getState());
    }

    public function testMultiStepJobFailsWhenEveryElementOfEveryStepFails(): void
    {
        $jobRun = $this->runJob(2, 5, [0 => [0, 1, 2, 3, 4], 1 => [0, 1, 2, 3, 4]]);

        $this->assertSame(JobRunStates::FAILED, $jobRun->getState());
    }

    /**
     * Runs a job to completion like a single worker would: the queued messages are handled in order, and a message
     * fails when the position of its element is listed for its step.
     *
     * @param array<int, array<int|null>> $failingPositionsByStep null stands for the message without element
     */
    private function runJob(int $stepCount, int $elementCount, array $failingPositionsByStep): JobRun
    {
        $elements = [];
        for ($position = 0; $position < $elementCount; $position++) {
            $elements[] = new ElementDescriptor('object', $position + 1);
        }
        $jobRun = $this->createRunningJobRun($elements, $stepCount);

        $queue = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            static function (object $message) use (&$queue): Envelope {
                $queue[] = $message;

                return new Envelope($message);
            }
        );

        $processedElementsByStep = [];
        $counter = $this->createMock(JobRunElementCounterInterface::class);
        $counter->method('incrementProcessedElementsForStep')->willReturnCallback(
            static function (JobRun $jobRun) use (&$processedElementsByStep): int {
                return $processedElementsByStep[$jobRun->getCurrentStep()] =
                    ($processedElementsByStep[$jobRun->getCurrentStep()] ?? 0) + 1;
            }
        );

        $errorLogRows = 0;
        $errorLogRepository = $this->createMock(JobRunErrorLogRepositoryInterface::class);
        $errorLogRepository->method('createFromJobRun')->willReturnCallback(
            static function () use (&$errorLogRows): void {
                $errorLogRows++;
            }
        );
        $errorLogRepository->method('getTotalCountByJobRunId')->willReturnCallback(
            static function () use (&$errorLogRows): int {
                return $errorLogRows;
            }
        );

        $agent = $this->createAgent($this->createRepository($jobRun), $counter, $errorLogRepository, $messageBus);
        $agent->startJobExecution($jobRun->getJob(), 1);

        while ($queue !== []) {
            $message = array_shift($queue);
            $position = $message->getElement() === null ? null : $message->getElement()->getId() - 1;
            $fails = in_array($position, $failingPositionsByStep[$message->getCurrentJobStep()] ?? [], true);

            $agent->continueJobMessageExecution($message, $fails ? new RuntimeException('element failed') : null);
        }

        return $jobRun;
    }

    private function createRepository(JobRun $jobRun): JobRunRepositoryInterface
    {
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->method('createFromJob')->willReturn($jobRun);
        $repository->method('getJobRunById')->willReturn($jobRun);
        $repository->method('update')->willReturn($jobRun);

        return $repository;
    }

    /**
     * @param ElementDescriptor[] $elements
     */
    private function createRunningJobRun(array $elements, int $stepCount = 1): JobRun
    {
        $jobRun = new JobRun(1);
        // the id is normally assigned by Doctrine
        new ReflectionProperty(JobRun::class, 'id')->setValue($jobRun, self::JOB_RUN_ID);
        $steps = [];
        for ($step = 0; $step < $stepCount; $step++) {
            $steps[] = new JobStep('test_step_' . $step, ProcessElementMessage::class, '', []);
        }
        $jobRun->setJob(new Job('test_job', $steps, $elements));
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(count($elements));

        return $jobRun;
    }

    private function createAgent(
        JobRunRepositoryInterface $repository,
        JobRunElementCounterInterface $counter,
        ?JobRunErrorLogRepositoryInterface $errorLogRepository = null,
        ?MessageBusInterface $messageBus = null
    ): JobExecutionAgent {
        return new JobExecutionAgent(
            'test',
            ErrorHandlingMode::CONTINUE_ON_ERROR->value,
            $this->createMock(ExecutionContextInterface::class),
            $repository,
            $errorLogRepository ?? $this->createMock(JobRunErrorLogRepositoryInterface::class),
            $counter,
            new NullLogger(),
            $messageBus ?? $this->createMock(MessageBusInterface::class),
            $this->createMock(Translator::class)
        );
    }

    private function createMessage(ElementDescriptor $element): GenericExecutionEngineMessageInterface
    {
        return new class(self::JOB_RUN_ID, 0, $element) extends AbstractExecutionEngineMessage {
        };
    }
}
