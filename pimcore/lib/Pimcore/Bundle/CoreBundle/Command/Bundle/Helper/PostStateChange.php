<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command\Bundle\Helper;

use Pimcore\Console\Application;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Tool\AssetsInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;

class PostStateChange
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public static function configureStateChangeCommandOptions(Command $command)
    {
        $command->addOption(
            'no-post-change-commands',
            null,
            InputOption::VALUE_NONE,
            'Do not run any post change commands (<comment>assets:install</comment>, <comment>cache:clear</comment>) after successful state change'
        );

        $command->addOption(
            'no-assets-install',
            null,
            InputOption::VALUE_NONE,
            'Do not run <comment>assets:install</comment> command after successful state change'
        );

        $command->addOption(
            'no-cache-clear',
            null,
            InputOption::VALUE_NONE,
            'Do not run <comment>cache:clear</comment> command after successful state change'
        );
    }

    public function runPostStateChangeCommands(PimcoreStyle $io)
    {
        $input = $io->getInput();

        if ($input->getOption('no-post-change-commands')) {
            return;
        }

        $runAssetsInstall = $input->getOption('no-assets-install') ? false : true;
        $runCacheClear = $input->getOption('no-cache-clear') ? false : true;

        $commands = [];

        if ($runAssetsInstall) {
            $commands[] = ['assets:install', $this->resolveAssetsInstallOptions()];
        }

        if ($runCacheClear) {
            $commands[] = ['cache:clear'];
        }

        if (empty($commands)) {
            return;
        }

        $io->newLine();
        $io->section('Running post state change commands');

        foreach ($commands as $command) {
            $this->runCommand($io, $command[0], $command[1] ?? []);
        }
    }

    private function runCommand(PimcoreStyle $io, string $command, array $arguments = [])
    {
        $this->writeCommandInfo($io, $command, $arguments);

        $command = $this->application->find($command);
        $arguments = array_merge(['command' => $command], $arguments);

        $code = $command->run(new ArrayInput($arguments), $io);
        if ($code > 0) {
            throw new \RuntimeException(sprintf('Command "%s" failed', $command));
        }
    }

    private function resolveAssetsInstallOptions(): array
    {
        $assetsInstaller = $this->application->getKernel()->getContainer()->get(AssetsInstaller::class);

        $assetsOptions = [];
        foreach ($assetsInstaller->resolveOptions(['ansi' => true]) as $option => $value) {
            // do not set ansi option again for our subcommand
            if ('ansi' === $option) {
                continue;
            }

            $assetsOptions['--' . $option] = $value;
        }

        return $assetsOptions;
    }

    /**
     * Prints information about the command about to be run
     *
     * @param string $command
     * @param array $arguments
     */
    private function writeCommandInfo(PimcoreStyle $io, string $command, array $arguments)
    {
        $argumentsInfo = [];
        foreach ($arguments as $key => $value) {
            if (0 === strpos($key, '--')) {
                // --option
                $option = $key;

                if (is_bool($value)) {
                    if (!$value) {
                        $option .= '=0';
                    }
                } else {
                    $option .= sprintf('="%s"', $value);
                }

                $argumentsInfo[] = $option;
            } else {
                // arguments just add the value
                $argumentsInfo[] = sprintf('"%s"', $value);
            }
        }

        $commandInfo = $command;
        if (!empty($argumentsInfo)) {
            $commandInfo .= ' ' . implode(' ', $argumentsInfo);
        }

        $io->comment(sprintf('Running command %s', $commandInfo));
    }
}
