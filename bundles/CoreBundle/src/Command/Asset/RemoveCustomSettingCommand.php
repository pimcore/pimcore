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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:remove-custom-setting',
    description: 'Removes a custom setting from assets',
)]
class RemoveCustomSettingCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputOption::VALUE_REQUIRED, 'Name of the custom setting to remove')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'only remove custom-setting for assets with this (IDs)'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only remove custom-setting for assets in this folder (ID)'
            )
            ->addOption(
                'path-pattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'only remove custom-setting for the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditions = ['customSettings LIKE ?'];
        $conditionVariables = ['%"' . $input->getArgument('name') . '"%'];

        if ($input->getOption('parent')) {
            $parent = Asset::getById((int) $input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . Helper::escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit;
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        if ($regex = $input->getOption('path-pattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            $assets = $list->load();
            foreach ($assets as $asset) {
                $output->writeln($asset->getFullPath());
                $asset->removeCustomSetting($input->getArgument('name'));
                $asset->getDao()->updateCustomSettings();
            }

            Pimcore::collectGarbage();
        }

        return 0;
    }
}
