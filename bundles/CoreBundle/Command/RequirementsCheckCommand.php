<?php
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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Tool\Requirements;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RequirementsCheckCommand extends AbstractCommand
{
    /** @var array $levelsToDisplay */
    protected $levelsToDisplay = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:system:requirements:check')
            ->setAliases(['system:requirements:check'])
            ->setDescription('Check system requirements')
            ->addOption('min-level', 'l', InputOption::VALUE_OPTIONAL, "Minimum status level to report: 'warning' or 'error'");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
     * @param string $title
     *
     * @return void
     */
    protected function display(array $checks, string $title = ''): void
    {
        $checksTab = [];

        foreach ($checks as $check) {
            /** @var Requirements\Check $check */
            if (in_array($check->getState(), $this->levelsToDisplay)) {
                $checksTab[] = [$check->getName(), $this->displayState($check->getState())];
            }
        }

        if (!empty($checksTab)) {
            $this->io->table(["<options=bold>$title</>", ''], $checksTab);
        }
    }

    /**
     * @param string $state
     *
     * @return string
     */
    protected function displayState(string $state): string
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
