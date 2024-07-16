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

use Exception;
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
    name: 'pimcore:assets:generate-checksums',
    description: 'Re-generates checksum for specific or all assets',
)]
class GenerateChecksumCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'only generate checksum for assets with this (IDs)'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only generate checksum for assets in this folder (ID)'
            )
            ->addOption(
                'missing-only',
                'm',
                InputOption::VALUE_NONE,
                'only generate checksum for assets which have no checksum yet'
            )
            ->addOption(
                'path-pattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'only generate checksum for the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditionVariables = [];
        $missingOnly = $input->getOption('missing-only');

        $conditions = [];

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
                try {
                    if ($missingOnly && $asset->getCustomSetting('checksum')) {
                        continue;
                    }

                    if ($asset->getType() === 'folder') {
                        continue;
                    }

                    $this->output->writeln(' generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                    $asset->generateChecksum();
                } catch (Exception $e) {
                    $this->output->writeln(' error generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                }
            }

            Pimcore::collectGarbage();
        }

        return 0;
    }
}
