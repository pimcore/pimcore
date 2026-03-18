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

namespace Pimcore\Bundle\InstallBundle\BundleConfig;

use Pimcore\Bundle\InstallBundle\Console\ConsoleCommandRunner;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Registers bundles in bundles.php and runs pimcore:bundle:install for each.
 *
 * @internal
 */
final class BundleInstaller
{
    public function __construct(
        private readonly ConsoleCommandRunner $commandRunner,
        private readonly BundleWriter $bundleWriter = new BundleWriter(),
    ) {
    }

    public function registerBundles(InstallProfileInterface $profile): void
    {
        $bundles = $profile->getBundles();

        if ($bundles === []) {
            return;
        }

        $this->bundleWriter->addBundlesToConfig($bundles, $bundles);
    }

    public function installBundles(InstallProfileInterface $profile, SymfonyStyle $io): void
    {
        $bundles = $profile->getBundles();
        $total = count($bundles);

        foreach ($bundles as $index => $bundleFqcn) {
            if ($this->isBundleInstalled($bundleFqcn)) {
                $io->text(sprintf(
                    '  [%d/%d] %s ... already installed',
                    $index + 1,
                    $total,
                    $this->getShortBundleName($bundleFqcn),
                ));

                continue;
            }

            $io->text(sprintf(
                '  [%d/%d] %s ...',
                $index + 1,
                $total,
                $this->getShortBundleName($bundleFqcn),
            ));

            $this->commandRunner->runCommand(
                ['pimcore:bundle:install', $bundleFqcn],
                'Installing ' . $this->getShortBundleName($bundleFqcn),
                $io,
            );
        }
    }

    public function isBundleInstalled(string $bundleFqcn): bool
    {
        $shortName = $this->getShortBundleName($bundleFqcn);

        return SettingsStore::get('BUNDLE_INSTALLED__' . $shortName, 'pimcore') !== null;
    }

    /**
     * Extract short bundle name from FQCN.
     * e.g. "Pimcore\Bundle\SeoBundle\PimcoreSeoBundle" -> "PimcoreSeoBundle"
     */
    public function getShortBundleName(string $bundleFqcn): string
    {
        $parts = explode('\\', $bundleFqcn);

        return end($parts);
    }
}
