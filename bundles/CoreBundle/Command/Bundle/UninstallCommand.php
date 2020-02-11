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

namespace Pimcore\Bundle\CoreBundle\Command\Bundle;

use Pimcore\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UninstallCommand extends AbstractBundleCommand
{
    /**
     * @var PostStateChange
     */
    private $postStateChangeHelper;

    public function __construct(PimcoreBundleManager $bundleManager, PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);

        $this->postStateChangeHelper = $postStateChangeHelper;
    }

    protected function configure()
    {
        $this
            ->setName($this->buildName('uninstall'))
            ->configureDescriptionAndHelp('Uninstalls a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to uninstall')
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $this->getBundle();

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $this->bundleManager->uninstall($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully uninstalled', $bundle->getName()));
        } catch (\Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return 0;
    }
}
