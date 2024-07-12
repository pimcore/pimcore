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

use Pimcore\Console\AbstractCommand;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
abstract class AbstractBundleCommand extends AbstractCommand
{
    public function __construct(protected PimcoreBundleManager $bundleManager, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @return $this
     */
    protected function configureDescriptionAndHelp(string $description, string $help = null): static
    {
        if (null === $help) {
            $help = 'Bundle can be passed as fully qualified class name or as bundle short name (e.g. <comment>PimcoreEcommerceFrameworkBundle</comment>).';
        }

        $this
            ->setDescription($description)
            ->setHelp(sprintf('%s. %s', $description, $help));

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureFailWithoutErrorOption(): static
    {
        $this->addOption(
            'fail-without-error',
            'f',
            InputOption::VALUE_NONE,
            'Just output a warning but do not return an error code if the command can\'t be executed'
        );

        return $this;
    }

    protected function buildName(string $name): string
    {
        return sprintf('pimcore:bundle:%s', $name);
    }

    protected function handlePrerequisiteError(string $message): int
    {
        if ($this->io->getInput()->getOption('fail-without-error')) {
            $this->io->warning($message);

            return 0;
        } else {
            $this->io->error($message);

            return 1;
        }
    }

    protected function getBundle(): PimcoreBundleInterface
    {
        $bundleId = $this->io->getInput()->getArgument('bundle');
        $bundleId = $this->normalizeBundleIdentifier($bundleId);

        $activeBundles = $this->bundleManager->getActiveBundles(false);

        $bundle = null;

        if (isset($activeBundles[$bundleId])) {
            // try to load bundle via fully qualified class name first
            $bundle = $activeBundles[$bundleId];
        } else {
            // fall back to fetching bundle from kernel with its logical name
            $kernel = $this->getApplication()->getKernel();
            $bundle = $kernel->getBundle($bundleId);
        }

        if (!$bundle instanceof PimcoreBundleInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Bundle "%s" does not implement %s',
                $bundle->getName(),
                PimcoreBundleInterface::class
            ));
        }

        return $bundle;
    }

    protected function setupInstaller(PimcoreBundleInterface $bundle): ?\Pimcore\Extension\Bundle\Installer\InstallerInterface
    {
        $installer = $this->bundleManager->getInstaller($bundle);
        if (null === $installer) {
            return null;
        }

        return $installer;
    }

    protected function normalizeBundleIdentifier(string $bundleIdentifier): string
    {
        return str_replace('/', '\\', $bundleIdentifier);
    }

    protected function getShortClassName(string $className): ?string
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
