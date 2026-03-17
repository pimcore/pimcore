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

namespace Pimcore\Bundle\InstallBundle\Console;

use Pimcore\Tool\Console;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Executes Symfony console commands as subprocesses.
 *
 * @internal
 */
final class ConsoleCommandRunner
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<string> $arguments Console command and its arguments (without php binary or bin/console)
     */
    public function runCommand(
        array $arguments,
        string $taskName,
        ?SymfonyStyle $io = null,
    ): void {
        array_splice($arguments, 0, 0, [
            Console::getPhpCli(),
            PIMCORE_PROJECT_ROOT . '/bin/console',
        ]);

        $this->logger->info('Running {command} command', [
            'command' => implode(' ', $arguments),
        ]);

        $process = new Process($arguments);
        $process->setTimeout(0);
        $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);
        $process->run();

        if (!$process->isSuccessful()) {
            $e = new ProcessFailedException($process);
            $this->logger->error($e->getMessage());

            if ($io !== null) {
                $errorOutput = trim($process->getErrorOutput());
                if ($errorOutput !== '') {
                    $io->getErrorStyle()->write($errorOutput);
                }

                $io->getErrorStyle()->note(
                    $taskName . ' failed. Please run the following command manually:',
                );
                $io->getErrorStyle()->writeln(
                    '  ' . str_replace(
                        ["'", '\\'],
                        ['', '\\\\'],
                        $process->getCommandLine(),
                    ),
                );
            }

            throw $e;
        }

        if ($io !== null) {
            $output = $process->getOutput();
            if ($output !== '') {
                $io->writeln($output);
            }
        }
    }

    public function rebuildClasses(): void
    {
        $this->runCommand(
            ['pimcore:deployment:classes-rebuild', '-c'],
            'Rebuilding class definitions',
        );
    }

    public function markMigrationsAsDone(): void
    {
        $this->runCommand(
            ['doctrine:migrations:sync-metadata-storage', '-q'],
            'Sync migrations metadata storage',
        );

        $this->runCommand(
            [
                'doctrine:migrations:version',
                '--all', '--add', '--prefix=Pimcore\\Bundle\\CoreBundle', '-n', '-q',
            ],
            'Marking all migrations as done',
        );
    }
}
