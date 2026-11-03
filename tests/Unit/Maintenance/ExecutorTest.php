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

namespace Pimcore\Tests\Unit\Maintenance;

use Exception;
use Pimcore\Maintenance\Executor;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Covers the duration reported for each maintenance task.
 *
 * @internal
 */
class ExecutorTest extends TestCase
{
    protected function needsDb(): bool
    {
        return false;
    }

    private function executor(TaskInterface $task, AbstractLogger $logger): Executor
    {
        $executor = new Executor('maintenance.pid', $logger, $this->createMock(MessageBusInterface::class));
        $executor->registerTask('task', $task);

        return $executor;
    }

    private function logger(array &$records): AbstractLogger
    {
        return new class($records) extends AbstractLogger {
            public function __construct(private array &$records)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }

    public function testASuccessfulTaskReportsItsDuration(): void
    {
        $records = [];
        $task = $this->createMock(TaskInterface::class);
        $task->expects($this->once())->method('execute');

        $this->executor($task, $this->logger($records))->executeTask('task');

        $finished = array_values(array_filter($records, static fn (array $r) => str_contains($r['message'], 'Finished job')));
        $this->assertCount(1, $finished);
        $this->assertStringContainsString('{duration}s', $finished[0]['message']);
        $this->assertArrayHasKey('duration', $finished[0]['context']);
        $this->assertGreaterThanOrEqual(0, $finished[0]['context']['duration']);
    }

    public function testAFailingTaskAlsoReportsItsDuration(): void
    {
        $records = [];
        $task = $this->createMock(TaskInterface::class);
        $task->method('execute')->willThrowException(new Exception('boom'));

        $this->executor($task, $this->logger($records))->executeTask('task');

        $failed = array_values(array_filter($records, static fn (array $r) => str_contains($r['message'], 'Failed to execute job')));
        $this->assertCount(1, $failed);
        $this->assertStringContainsString('{duration}s', $failed[0]['message']);
        $this->assertArrayHasKey('duration', $failed[0]['context']);
        $this->assertGreaterThanOrEqual(0, $failed[0]['context']['duration']);
    }
}
