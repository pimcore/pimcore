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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends AbstractBundleCommand
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
            ->setName($this->buildName('enable'))
            ->configureDescriptionAndHelp(
                'Enables a bundle',
                'The class name can also be specified with slashes for easier handling on the command line. (e.g. <comment>Pimcore/Bundle/EcommerceFramework/PimcoreEcommerceFrameworkBundle</comment>)'
            )
            ->addArgument(
                'bundle-class',
                InputArgument::REQUIRED,
                'The bundle class name to enable, without the namespace, eg. MySpecialBundle'
            )
            ->addOption(
                'priority',
                'p',
                InputOption::VALUE_REQUIRED,
                'Optional priority to configure'
            )
            ->addOption(
                'environments',
                'E',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'If defined, the bundle will be configured to only be loaded in the specified environments'
            )
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $this->resolveState($input);

        $bundleClass = $this->normalizeBundleIdentifier($input->getArgument('bundle-class'));

        $mapping = $this->getAvailableBundleShortNameMapping($this->bundleManager);
        if (isset($mapping[$bundleClass])) {
            $bundleClass = $mapping[$bundleClass];
        }

        try {
            $this->bundleManager->enable($bundleClass, $state);

            $this->io->success(sprintf('Bundle "%s" was successfully enabled', $bundleClass));
        } catch (\Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return 0;
    }

    /**
     * Maps short name without namespace to fully qualified name to avoid having to use the fully qualified name
     * as argument.
     *
     * e.g. PimcoreEcommerceFrameworkBundle => Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle
     *
     * @param PimcoreBundleManager $bundleManager
     *
     * @return array
     */
    private function getAvailableBundleShortNameMapping(PimcoreBundleManager $bundleManager): array
    {
        $availableBundles = $bundleManager->getAvailableBundles();

        $mapping = [];
        foreach ($availableBundles as $availableBundle) {
            $mapping[$this->getShortClassName($availableBundle)] = $availableBundle;
        }

        return $mapping;
    }

    private function resolveState(InputInterface $input): array
    {
        $state = [];

        $priority = $input->getOption('priority');
        if (null !== $priority) {
            $state['priority'] = (int)$priority;
        }

        $environments = $input->getOption('environments');
        if (!empty($environments)) {
            $state['environments'] = $environments;
        }

        return $state;
    }
}
