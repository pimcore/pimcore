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

namespace Pimcore\Tests\Unit\Helper;

use Closure;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Pimcore;
use Pimcore\Helper\StopMessengerWorkersTrait;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Stopping the messenger workers is a signal on top of the operation of the caller (a configuration
 * was saved, a class definition was updated, ...). Since symfony/messenger 7.4.24 the command
 * `messenger:stop-workers` fails if it cannot store that signal, e.g. on the database cache in a
 * migration that runs after a migration with an implicit commit. This must not fail the caller.
 */
class StopMessengerWorkersTraitTest extends TestCase
{
    private const string COMMAND = 'messenger:stop-workers';

    private TestHandler $logHandler;

    private ?Closure $commandListener = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->getPimcoreLogger()->pushHandler($this->logHandler);
    }

    protected function tearDown(): void
    {
        if ($this->commandListener !== null) {
            Pimcore::getEventDispatcher()->removeListener(ConsoleEvents::COMMAND, $this->commandListener);
            $this->commandListener = null;
        }

        $this->getPimcoreLogger()->popHandler();

        parent::tearDown();
    }

    public function testFailingCommandIsLoggedAndDoesNotFailTheCaller(): void
    {
        $this->letStopWorkersCommandFail();

        $this->stopMessengerWorkers();

        $this->assertTrue(
            $this->logHandler->hasErrorThatContains(self::COMMAND),
            'a signal that could not be sent must be logged as an error'
        );
    }

    public function testSuccessfulCommandIsNotLogged(): void
    {
        $this->stopMessengerWorkers();

        $this->assertFalse($this->logHandler->hasErrorThatContains(self::COMMAND));
    }

    private function stopMessengerWorkers(): void
    {
        $subject = new class() {
            use StopMessengerWorkersTrait {
                stopMessengerWorkers as public;
            }
        };

        $subject->stopMessengerWorkers();
    }

    /**
     * A disabled command is not executed and makes the console application return a non-zero exit
     * code, like the command itself does when the signal cannot be stored.
     */
    private function letStopWorkersCommandFail(): void
    {
        $this->commandListener = static function (ConsoleCommandEvent $event): void {
            if ($event->getCommand()?->getName() === self::COMMAND) {
                $event->disableCommand();
            }
        };

        Pimcore::getEventDispatcher()->addListener(ConsoleEvents::COMMAND, $this->commandListener);
    }

    private function getPimcoreLogger(): Logger
    {
        $logger = Pimcore::getContainer()->get('monolog.logger.pimcore');
        $this->assertInstanceOf(Logger::class, $logger);

        return $logger;
    }
}
