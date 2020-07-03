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

use Pimcore\Cache\Symfony\CacheClearer;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Tool\AssetsInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PostStateChange
{
    /**
     * @var CacheClearer
     */
    private $cacheClearer;

    /**
     * @var AssetsInstaller
     */
    private $assetsInstaller;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        CacheClearer $cacheClearer,
        AssetsInstaller $assetsInstaller,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->cacheClearer = $cacheClearer;
        $this->assetsInstaller = $assetsInstaller;
        $this->eventDispatcher = $eventDispatcher;
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

    public function runPostStateChangeCommands(PimcoreStyle $io, string $environment)
    {
        $input = $io->getInput();

        if ($input->getOption('no-post-change-commands')) {
            return;
        }

        $runAssetsInstall = $input->getOption('no-assets-install') ? false : true;
        $runCacheClear = $input->getOption('no-cache-clear') ? false : true;

        if (!$runAssetsInstall && !$runCacheClear) {
            return;
        }

        $runCallback = function ($type, $buffer) use ($io) {
            $io->write($buffer);
        };

        $io->newLine();
        $io->section('Running post state change commands');

        if ($runAssetsInstall) {
            $io->comment('Running bin/console assets:install...');

            try {
                $this->assetsInstaller->setRunCallback($runCallback);
                $this->assetsInstaller->install([
                    'env' => $environment,
                    'ansi' => $io->isDecorated(),
                ]);
            } catch (ProcessFailedException $e) {
                // noop - output should be enough
            }
        }

        if ($runCacheClear) {
            // remove terminate event listeners as they break with a cleared container
            foreach ($this->eventDispatcher->getListeners(ConsoleEvents::TERMINATE) as $listener) {
                $this->eventDispatcher->removeListener(ConsoleEvents::TERMINATE, $listener);
            }

            $io->comment('Running bin/console cache:clear...');

            try {
                $this->cacheClearer->setRunCallback($runCallback);
                $this->cacheClearer->clear($environment, [
                    'ansi' => $io->isDecorated(),
                ]);
            } catch (ProcessFailedException $e) {
                // noop - output should be enough
            }
        }
    }
}
