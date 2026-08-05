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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class UninstallCommand extends AbstractBundleCommand
{
    public function __construct(PimcoreBundleManager $bundleManager, private PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->buildName('uninstall'))
            ->configureDescriptionAndHelp('Uninstalls a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to uninstall')
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->getBundle();

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $this->bundleManager->uninstall($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully uninstalled', $bundle->getName()));
        } catch (Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return 0;
    }
}
