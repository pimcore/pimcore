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

use const JSON_PRETTY_PRINT;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class ListCommand extends AbstractBundleCommand
{
    protected function configure(): void
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
            ->addOption(
                'details',
                'd',
                InputOption::VALUE_NONE,
                'Show more details of pimcore bundles e.g. version, description etc.'
            )
            ->addOption('json', null, InputOption::VALUE_NONE, 'Return data as JSON')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $details = $input->getOption('details');
        $returnData = [
            'headers' => [
                'Bundle',
                'Enabled',
                'Installed',
                $input->hasOption('json') ? 'Installable' : 'I?',
                $input->hasOption('json') ? 'Uninstallable' : 'UI?',
                'Priority',
            ],
            'rows' => [],
        ];

        if ($details) {
            array_splice($returnData['headers'], 1, 0, ['Description', 'Version']);
        }

        foreach ($this->bundleManager->getAvailableBundles() as $bundleClass) {
            $row = [];
            $row[] = $input->getOption('fully-qualified-classnames') ? $bundleClass : $this->getShortClassName($bundleClass);

            try {
                $bundle = $this->bundleManager->getActiveBundle($bundleClass, false);
                if ($details) {
                    $row[] = substr($bundle->getDescription(), 0, 30) . (strlen($bundle->getDescription()) > 30 ? '...' : '');
                    $row[] = $bundle->getVersion();
                }
                $row[] = true;
                $row[] = $this->bundleManager->isInstalled($bundle);
                $row[] = $this->bundleManager->canBeInstalled($bundle);
                $row[] = $this->bundleManager->canBeUninstalled($bundle);
                $row[] = $this->bundleManager->getManuallyRegisteredBundleState($bundleClass)['priority'];
            } catch (BundleNotFoundException $e) {
                if ($details) {
                    $row[] = '';
                    $row[] = '';
                }
                $row[] = $row[] = $row[] = $row[] = false;
                $row[] = 0;
            }

            $returnData['rows'][] = $row;
        }

        if ($input->getOption('json')) {
            $jsonData = array_map(fn ($row) => array_combine($returnData['headers'], $row), $returnData['rows']);
            $output->write(json_encode($jsonData, JSON_PRETTY_PRINT));
        } else {
            $table = new Table($output);

            $table->setHeaders($returnData['headers']);

            $returnData['rows'] = array_map(function ($row) {
                foreach ($row as $idx => $column) {
                    if (is_bool($column)) {
                        $row[$idx] = $this->formatBool($column);
                    }
                }

                return $row;
            }, $returnData['rows']);

            $table->addRows($returnData['rows']);

            $table->render();

            $this->io->newLine();
            $this->io->writeln(implode(' ', [
                'Legend:',
                '<comment>I?</comment>: Can be installed?',
                '<comment>UI?</comment>: Can be uninstalled?',
                '<comment>UP?</comment>: Can be updated?',
            ]));
        }

        return 0;
    }

    private function formatBool(bool $state): string
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
