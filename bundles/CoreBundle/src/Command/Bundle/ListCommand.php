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

use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function json_encode;
use function Symfony\Component\String\s;
use const JSON_PRETTY_PRINT;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:bundle:list',
    description: 'Lists all pimcore bundles and their enabled/installed state'
)]
class ListCommand extends AbstractBundleCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'fully-qualified-classnames',
            'f',
            InputOption::VALUE_NONE,
            'Show fully qualified class names instead of short names'
        )->addOption(
            'details',
            'd',
            InputOption::VALUE_NONE,
            'Show more details of pimcore bundles e.g. version, description etc.'
        )->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Return data as JSON'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $details = $input->getOption('details');
        $headers = [
            'Bundle',
            'Enabled',
            'Installed',
            'Installable',
            'Uninstallable',
            'Priority',
        ];
        $rows = [];

        if ($details) {
            array_splice($headers, 1, 0, ['Description', 'Version']);
        }

        foreach ($this->bundleManager->getAvailableBundles() as $bundleClass) {
            $row = [
                $input->getOption('fully-qualified-classnames')
                    ? $bundleClass
                    : $this->getShortClassName($bundleClass)
            ];

            try {
                $bundle = $this->bundleManager->getActiveBundle($bundleClass, false);

                if ($details) {
                    array_push(
                        $row,
                        s($bundle->getDescription())->truncate(30, '...'),
                        $bundle->getVersion(),
                    );
                }

                array_push(
                    $row,
                    true,
                    $this->bundleManager->isInstalled($bundle),
                    $this->bundleManager->canBeInstalled($bundle),
                    $this->bundleManager->canBeUninstalled($bundle),
                    $this->bundleManager->getManuallyRegisteredBundleState($bundleClass)['priority'],
                );
            } catch (BundleNotFoundException) {
                if ($details) {
                    array_push($row, '', '');
                }

                array_push($row, false, false, false, false, 0);
            }

            $rows[] = $row;
        }

        if ($input->getOption('json') === true) {
            $jsonData = array_map(
                static fn (array $row) => array_combine($headers, $row),
                $rows
            );

            $this->io->write(
                json_encode(
                    $jsonData,
                    JSON_PRETTY_PRINT
                )
            );

            return self::SUCCESS;
        }

        $rows  = array_map(
            static fn (array $row) => array_map(
                static function (bool|string|int $column) use ($output) {
                    if (is_bool($column)) {
                        if ($column === true) {
                            return sprintf(
                                '<fg=green>%s</>',
                                $output->isDecorated() ? "\xE2\x9C\x94" : 'yes'
                            );
                        }

                        return sprintf(
                            '<fg=red>%s</>',
                            $output->isDecorated() ? "\xE2\x9D\x8C" : 'no'
                        );
                    }

                    return $column;
                },
                $row
            ),
            $rows
        );

        $this->io->table($headers, $rows);

        return self::SUCCESS;
    }
}
