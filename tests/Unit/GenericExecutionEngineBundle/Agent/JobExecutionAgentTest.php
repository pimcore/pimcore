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
use Pimcore\Translation\Translator;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;
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
        $agent = $this->createAgent($repository, $counter);

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
        $this->assertSame(JobRunStates::FAILED, $jobRun->getState());
    }

    private function createRepository(JobRun $jobRun): JobRunRepositoryInterface
    {
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->method('getJobRunById')->willReturn($jobRun);
        $repository->method('update')->willReturn($jobRun);

        return $repository;
    }

    /**
     * @param ElementDescriptor[] $elements
     */
    private function createRunningJobRun(array $elements): JobRun
    {
        $jobRun = new JobRun(1);
        // the id is normally assigned by Doctrine
        new ReflectionProperty(JobRun::class, 'id')->setValue($jobRun, self::JOB_RUN_ID);
        $jobRun->setJob(
            new Job(
                'test_job',
                [new JobStep('test_step', AbstractExecutionEngineMessage::class, '', [])],
                $elements
            )
        );
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(count($elements));

        return $jobRun;
    }

    private function createAgent(
        JobRunRepositoryInterface $repository,
        JobRunElementCounterInterface $counter
    ): JobExecutionAgent {
        return new JobExecutionAgent(
            'test',
            ErrorHandlingMode::CONTINUE_ON_ERROR->value,
            $this->createMock(ExecutionContextInterface::class),
            $repository,
            $this->createMock(JobRunErrorLogRepositoryInterface::class),
            $counter,
            new NullLogger(),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(Translator::class)
        );
    }

    private function createMessage(ElementDescriptor $element): GenericExecutionEngineMessageInterface
    {
        return new class(self::JOB_RUN_ID, 0, $element) extends AbstractExecutionEngineMessage {
        };
    }
}
