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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Tool\Requirements;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name:'pimcore:system:requirements:check',
    description: 'Check system requirements',
    aliases: ['system:requirements:check']
)]
class RequirementsCheckCommand extends AbstractCommand
{
    /** @var int[] $levelsToDisplay */
    protected array $levelsToDisplay = [];

    protected function configure(): void
    {
        $this
            ->addOption('min-level', 'l', InputOption::VALUE_OPTIONAL, "Minimum status level to report: 'warning' or 'error'");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($input->getOption('min-level')) {
            case 'warning':
            case 'warnings':
                $this->levelsToDisplay = [Requirements\Check::STATE_WARNING, Requirements\Check::STATE_ERROR];

                break;
            case 'error':
            case 'errors':
                $this->levelsToDisplay = [Requirements\Check::STATE_ERROR];

                break;
            default:
                $this->levelsToDisplay = [Requirements\Check::STATE_OK, Requirements\Check::STATE_WARNING, Requirements\Check::STATE_ERROR];

                break;
        }

        $allChecks = Requirements::checkAll(Db::get());

        $this->display($allChecks['checksPHP'], 'PHP');
        $this->display($allChecks['checksMySQL'], 'MySQL');
        $this->display($allChecks['checksFS'], 'Filesystem');
        $this->display($allChecks['checksApps'], 'CLI Tools & Applications');

        return 0;
    }

    /**
     * @param Requirements\Check[] $checks
     */
    protected function display(array $checks, string $title = ''): void
    {
        $checksTab = [];

        foreach ($checks as $check) {
            if (in_array($check->getState(), $this->levelsToDisplay)) {
                $checksTab[] = [$check->getName(), $this->displayState($check->getState())];
            }
        }

        if (!empty($checksTab)) {
            $this->io->table(["<options=bold>$title</>", ''], $checksTab);
        }
    }

    protected function displayState(int $state): string
    {
        switch ($state) {
            case Requirements\Check::STATE_OK:
                $displayState = '<fg=green>ok</>';

                break;
            case Requirements\Check::STATE_WARNING:
                $displayState = '<fg=yellow>warning</>';

                break;
            case Requirements\Check::STATE_ERROR:
            default:
                $displayState = '<fg=red>error</>';

                break;
        }

        return $displayState;
    }
}
