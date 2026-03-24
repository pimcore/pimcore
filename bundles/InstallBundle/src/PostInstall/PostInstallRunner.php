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

namespace Pimcore\Bundle\InstallBundle\PostInstall;

use Pimcore\Bundle\InstallBundle\BundleConfig\BundleNameHelper;
use Pimcore\Bundle\InstallBundle\Console\ConsoleCommandRunner;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommand;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommandsProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Collects post-install commands from multiple sources, deduplicates,
 * sorts by priority, and executes them.
 *
 * @internal
 */
final class PostInstallRunner
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConsoleCommandRunner $commandRunner,
    ) {
    }

    /**
     * Collect post-install commands from profile, CLI providers, and bundle installers.
     * Deduplicate by command name (first wins), then sort by priority descending.
     *
     * @param list<PostInstallCommandsProviderInterface> $cliProviders
     *
     * @return list<PostInstallCommand>
     */
    public function collectPostInstallCommands(
        InstallProfileInterface $profile,
        array $cliProviders,
        KernelInterface $kernel,
    ): array {
        $commands = [];

        foreach ($profile->getPostInstallCommands() as $cmd) {
            $commands[$cmd->getCommand()] = $cmd;
        }

        foreach ($profile->getBundles() as $bundleFqcn) {
            try {
                $bundle = $kernel->getBundle(BundleNameHelper::getShortName($bundleFqcn));
                if (method_exists($bundle, 'getInstaller')) {
                    $installer = $bundle->getInstaller();
                    if ($installer instanceof PostInstallCommandsProviderInterface) {
                        foreach ($installer->getPostInstallCommands() as $cmd) {
                            if (!isset($commands[$cmd->getCommand()])) {
                                $commands[$cmd->getCommand()] = $cmd;
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->logger->warning(
                    'Could not collect post-install commands from bundle {bundle}: {error}',
                    ['bundle' => $bundleFqcn, 'error' => $e->getMessage()],
                );
            }
        }

        foreach ($cliProviders as $provider) {
            foreach ($provider->getPostInstallCommands() as $cmd) {
                if (!isset($commands[$cmd->getCommand()])) {
                    $commands[$cmd->getCommand()] = $cmd;
                }
            }
        }

        $sorted = array_values($commands);
        usort(
            $sorted,
            static fn (PostInstallCommand $a, PostInstallCommand $b) => $b->getPriority() <=> $a->getPriority(),
        );

        return $sorted;
    }

    /**
     * @param list<PostInstallCommand> $commands
     */
    public function runPostInstallCommands(array $commands, SymfonyStyle $io): void
    {
        $total = count($commands);

        foreach ($commands as $index => $command) {
            $io->text(sprintf(
                '  [%d/%d] %s ...',
                $index + 1,
                $total,
                $command->getLabel(),
            ));

            $args = array_merge([$command->getCommand()], $command->getArguments());
            $this->commandRunner->runCommand($args, $command->getLabel(), $io);
        }
    }
}
