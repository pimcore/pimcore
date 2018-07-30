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

use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractBundleCommand
{
    protected function configure()
    {
        $this
            ->setName($this->buildName('list'))
            ->setDescription('Lists all pimcore bundles and their enabled/installed state')
            ->addOption(
                'fully-qualified-classnames',
                'f',
                InputOption::VALUE_NONE,
                'Show fully qualified class names instead of short names'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Bundle',
            'Enabled',
            'Installed',
            'I?',
            'UI?',
            'UP?',
        ]);

        $bm = $this->getBundleManager();
        foreach ($bm->getAvailableBundles() as $bundleClass) {
            $enabled = $bm->isEnabled($bundleClass);

            /** @var PimcoreBundleInterface $bundle */
            $bundle = null;
            if ($enabled) {
                $bundle = $bm->getActiveBundle($bundleClass, false);
            }

            $row = [];

            if ($input->getOption('fully-qualified-classnames')) {
                $row[] = $bundleClass;
            } else {
                $row[] = $this->getShortClassName($bundleClass);
            }

            $row[] = $this->formatBool($enabled);

            if ($enabled) {
                $row[] = $this->formatBool($bm->isInstalled($bundle));
                $row[] = $this->formatBool($bm->canBeInstalled($bundle));
                $row[] = $this->formatBool($bm->canBeUninstalled($bundle));
                $row[] = $this->formatBool($bm->canBeUpdated($bundle));
            } else {
                $row[] = $this->formatBool(false);
                $row[] = $this->formatBool(false);
                $row[] = $this->formatBool(false);
                $row[] = $this->formatBool(false);
            }

            $table->addRow($row);
        }

        $table->render();

        $this->io->newLine();
        $this->io->writeln(implode(' ', [
            'Legend:',
            '<comment>I?</comment>: Can be installed?',
            '<comment>UI?</comment>: Can be uninstalled?',
            '<comment>UP?</comment>: Can be updated?',
        ]));
    }

    private function formatBool($state): string
    {
        $decorated = $this->io->getOutput()->isDecorated();

        if ($state) {
            return sprintf(
                '<fg=green>%s</>',
                $decorated ? "\xE2\x9C\x94" : 'yes'
            );
        } else {
            return sprintf(
                '<fg=red>%s</>',
                $decorated ? "\xE2\x9D\x8C" : 'no'
            );
        }
    }
}
