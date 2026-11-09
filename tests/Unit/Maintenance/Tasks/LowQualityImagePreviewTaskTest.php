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

namespace Pimcore\Tests\Unit\Maintenance\Tasks;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Pimcore\Maintenance\Tasks\LowQualityImagePreviewTask;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * The task throttles itself to one run per night (between 0:00 and 4:59) with a lock. The lock used
 * to be auto-released as soon as the lock object was destroyed, i.e. when the maintenance worker
 * exited, and the same worker could re-acquire its own lock on the next cycle, so the task ran on
 * every maintenance cycle of the night and retried every image without a preview each time.
 *
 * @internal
 */
final class LowQualityImagePreviewTaskTest extends TestCase
{
    private const FIRST_NIGHT = '2026-10-22 01:00:00';

    /**
     * Logged once per run that gets past the throttle, right before the images are processed.
     */
    private const RUN_LOG_MESSAGE = 'Execute low quality image preview generation';

    private LockFactory $lockFactory;

    private MockClock $clock;

    private TestHandler $logHandler;

    /**
     * execute() loads the image listing from the database
     */
    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFactory = new LockFactory(new InMemoryStore());
        $this->clock = new MockClock(self::FIRST_NIGHT);
        $this->logHandler = new TestHandler();
    }

    public function testTheSameWorkerRunsTheTaskOnlyOncePerNight(): void
    {
        $task = $this->createTask();

        $task->execute();
        $this->clock->sleep(600);
        $task->execute();

        $this->assertSame(1, $this->countRuns(), 'the next maintenance cycle of the same worker must not run the task again');
    }

    public function testANewWorkerRunsTheTaskOnlyOncePerNight(): void
    {
        $this->createTask()->execute();
        $this->clock->sleep(600);
        $this->createTask()->execute();

        $this->assertSame(1, $this->countRuns(), 'a new worker must not run the task again once the lock object of the first run is gone');
    }

    public function testTheTaskRunsAgainTheNextNight(): void
    {
        $task = $this->createTask();

        $task->execute();
        $this->clock->modify('+1 day');
        $task->execute();

        $this->assertSame(2, $this->countRuns(), 'the task must run once every night, not every other night');
    }

    public function testTheTaskDoesNotRunOutsideTheNightWindow(): void
    {
        $this->clock->modify('+4 hours');

        $this->createTask()->execute();

        $this->assertSame(0, $this->countRuns());
    }

    private function createTask(): LowQualityImagePreviewTask
    {
        return new LowQualityImagePreviewTask(
            new Logger('test', [$this->logHandler]),
            $this->lockFactory,
            $this->clock
        );
    }

    private function countRuns(): int
    {
        return count(array_filter(
            $this->logHandler->getRecords(),
            static fn (LogRecord $record): bool => $record->message === self::RUN_LOG_MESSAGE
        ));
    }
}
