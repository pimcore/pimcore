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

namespace Pimcore\Tests\Unit\Console;

use Codeception\Test\Unit;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Exception\CommandAlreadyRunningException;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Console\Traits\ParallelizationFallback;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;

/**
 * @group console.parallelization
 */
class ParallelizationLockTest extends Unit
{
    private const COMMAND = 'test:parallelization-lock';

    private ?int $terminateExitCode = null;

    public function testRunWhileAlreadyRunningEndsNormallyWithoutProcessingItems(): void
    {
        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock(self::COMMAND, 60);
        $this->assertTrue($lock->acquire());

        try {
            $command = new ParallelizationLockTestCommand();
            $tester = $this->runCommand($command);

            // Before: exit(1) from runBeforeFirstCommand() ended the PHP process here.
            $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
            $this->assertSame(Command::SUCCESS, $this->terminateExitCode, 'console.terminate was not dispatched');
            $this->assertStringContainsString('The command is already running.', $tester->getDisplay());
            $this->assertSame([], $command->processed);
        } finally {
            $lock->release();
        }
    }

    public function testRunProcessesItemsAndReleasesTheLock(): void
    {
        $command = new ParallelizationLockTestCommand();
        $tester = $this->runCommand($command);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(['a', 'b'], $command->processed);

        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock(self::COMMAND, 60);
        $this->assertTrue($lock->acquire(), 'the command did not release its lock');
        $lock->release();
    }

    /**
     * The sequential variant ParallelizationBase falls back to without webmozarts/console-parallelization
     * (a require-dev package): tested through its own trait, so CI runs it whether the package is installed or not.
     */
    public function testFallbackVariantEndsWithSuccessWhenTheCommandIsAlreadyRunning(): void
    {
        $command = new ParallelizationFallbackTestCommand();
        $command->alreadyRunning = true;
        $tester = $this->runCommand($command);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(Command::SUCCESS, $this->terminateExitCode, 'console.terminate was not dispatched');
        $this->assertSame([], $command->processed);
        $this->assertFalse($command->afterLastCommandRan, 'the run must end before the items and the cleanup');
    }

    public function testFallbackVariantProcessesItems(): void
    {
        $command = new ParallelizationFallbackTestCommand();
        $tester = $this->runCommand($command);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(['a', 'b'], $command->processed);
        $this->assertTrue($command->afterLastCommandRan);
    }

    private function runCommand(Command $command): ApplicationTester
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
            $this->terminateExitCode = $event->getExitCode();
        });

        $application = new Application();
        $application->setAutoExit(false);
        $application->setDispatcher($dispatcher);
        $application->addCommand($command);

        $input = ['command' => self::COMMAND];
        // --main-process: process the items here instead of spawning bin/console children.
        // Without webmozarts/console-parallelization the command has no such option and
        // always runs in this process (the fallback of ParallelizationBase).
        if ($command->getDefinition()->hasOption('main-process')) {
            $input['--main-process'] = true;
        }

        $tester = new ApplicationTester($application);
        $tester->run($input);

        return $tester;
    }
}

class ParallelizationLockTestCommand extends AbstractCommand
{
    use Parallelization;

    /** @var list<string> */
    public array $processed = [];

    protected function configure(): void
    {
        $this->setName('test:parallelization-lock');
        self::configureCommand($this);
    }

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        return ['a', 'b'];
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        $this->processed[] = $item;
    }
}

class ParallelizationFallbackTestCommand extends AbstractCommand
{
    use ParallelizationFallback;

    public bool $alreadyRunning = false;

    public bool $afterLastCommandRan = false;

    /** @var list<string> */
    public array $processed = [];

    protected function configure(): void
    {
        $this->setName('test:parallelization-lock');
    }

    protected function runBeforeFirstCommand(InputInterface $input, OutputInterface $output): void
    {
        if ($this->alreadyRunning) {
            throw new CommandAlreadyRunningException();
        }
    }

    protected function runAfterLastCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->afterLastCommandRan = true;
    }

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        return ['a', 'b'];
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        $this->processed[] = $item;
    }
}
