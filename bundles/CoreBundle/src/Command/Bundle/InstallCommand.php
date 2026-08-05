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

namespace Pimcore\Bundle\CoreBundle\Command\Bundle;

use Exception;
use Pimcore\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class InstallCommand extends AbstractBundleCommand
{
    public function __construct(PimcoreBundleManager $bundleManager, private PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->buildName('install'))
            ->configureDescriptionAndHelp('Installs a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to install')
            ->configureFailWithoutErrorOption()
        ;

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->getBundle();

        if ($this->bundleManager->isInstalled($bundle)) {
            $this->io->success(sprintf('Bundle "%s" is already installed', $bundle->getName()));

            return Command::SUCCESS;
        }

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $this->bundleManager->install($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully installed', $bundle->getName()));
        } catch (Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return Command::SUCCESS;
    }
}
