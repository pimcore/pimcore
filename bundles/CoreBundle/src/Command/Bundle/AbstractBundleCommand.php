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

use InvalidArgumentException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
abstract class AbstractBundleCommand extends AbstractCommand
{
    public function __construct(
        protected PimcoreBundleManager $bundleManager,
        ?string $name = null
    ) {
        parent::__construct($name);
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

    protected function completeBundleArgument(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('bundle') === true) {
            $suggestions->suggestValues(
                array_reduce(
                    $this->bundleManager->getActiveBundles(false),
                    static function (array $result, BundleInterface $bundle) {
                        $result[] = $bundle->getName();

                        return $result;
                    },
                    []
                )
            );
        }
    }

    protected function handlePrerequisiteError(string $message): int
    {
        if ($this->io->getInput()->getOption('fail-without-error')) {
            $this->io->warning($message);

            return self::SUCCESS;
        }

        $this->io->error($message);

        return self::FAILURE;
    }

    protected function getBundle(): PimcoreBundleInterface
    {
        $bundleId = $this->io->getInput()->getArgument('bundle');
        $bundleId = $this->normalizeBundleIdentifier($bundleId);

        $activeBundles = $this->bundleManager->getActiveBundles(false);

        if (isset($activeBundles[$bundleId])) {
            // try to load bundle via fully qualified class name first
            $bundle = $activeBundles[$bundleId];
        } else {
            // fall back to fetching bundle from kernel with its logical name
            $kernel = $this->getApplication()->getKernel();
            $bundle = $kernel->getBundle($bundleId);
        }

        if (!$bundle instanceof PimcoreBundleInterface) {
            throw new InvalidArgumentException(sprintf(
                'Bundle "%s" does not implement %s',
                $bundle->getName(),
                PimcoreBundleInterface::class
            ));
        }

        return $bundle;
    }

    protected function setupInstaller(PimcoreBundleInterface $bundle): ?InstallerInterface
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
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
