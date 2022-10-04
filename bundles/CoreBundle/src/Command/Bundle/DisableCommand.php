<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Command\Bundle;

use Pimcore\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @deprecated will be removed in Pimcore 11
 */
class DisableCommand extends AbstractBundleCommand
{
    public function __construct(PimcoreBundleManager $bundleManager, private PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure()
    {
        $this
            ->setName($this->buildName('disable'))
            ->configureDescriptionAndHelp('Disables a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to disable')
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deprecation = 'Disabling bundle is deprecated and will not work in Pimcore 11. Use config/bundles.php to register/de-register bundles instead.';
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5',
            $deprecation
        );

        if ($output->isVerbose()) {
            $output->writeln(sprintf('Since pimcore/pimcore 10.5, %s', $deprecation));
        }

        $bundle = $this->getBundle();

        try {
            $this->bundleManager->disable($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully disabled', $bundle->getName()));
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
