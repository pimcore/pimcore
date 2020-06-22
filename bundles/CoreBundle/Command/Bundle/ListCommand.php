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
            ->addOption('json', null, InputOption::VALUE_NONE, 'Return data as JSON')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $returnData = [
            'headers' => [
                'Bundle',
                'Enabled',
                'Installed',
                $input->hasOption('json') ? 'Installable' : 'I?',
                $input->hasOption('json') ? 'Uninstallable' : 'UI?',
                $input->hasOption('json') ? 'Updatable' : 'UP?',
                'Priority',
            ],
        ];

        foreach ($this->bundleManager->getAvailableBundles() as $bundleClass) {
            $enabled = $this->bundleManager->isEnabled($bundleClass);

            /** @var PimcoreBundleInterface $bundle */
            $bundle = null;
            if ($enabled) {
                $bundle = $this->bundleManager->getActiveBundle($bundleClass, false);
            }

            $row = [];

            if ($input->getOption('fully-qualified-classnames')) {
                $row[] = $bundleClass;
            } else {
                $row[] = $this->getShortClassName($bundleClass);
            }

            $row[] = $enabled;

            if ($enabled) {
                $row[] = $this->bundleManager->isInstalled($bundle);
                $row[] = $this->bundleManager->canBeInstalled($bundle);
                $row[] = $this->bundleManager->canBeUninstalled($bundle);
                $row[] = $this->bundleManager->canBeUpdated($bundle);

                $bundleState = $this->bundleManager->getState($bundle);
                $row[] = $bundleState['priority'];
            } else {
                $row[] = false;
                $row[] = false;
                $row[] = false;
                $row[] = false;
                $row[] = 0;
            }

            $returnData['rows'][] = $row;
        }

        if ($input->getOption('json')) {
            $jsonData = array_map(static function ($row) use ($returnData) {
                return array_combine($returnData['headers'], $row);
            }, $returnData['rows']);
            $output->write(\json_encode($jsonData, \JSON_PRETTY_PRINT));
        } else {
            $table = new Table($output);

            $table->setHeaders($returnData['headers']);

            $returnData['rows'] = array_map(function ($row) {
                for ($i = 1; $i <= 5; $i++) {
                    $row[$i] = $this->formatBool($row[$i]);
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
