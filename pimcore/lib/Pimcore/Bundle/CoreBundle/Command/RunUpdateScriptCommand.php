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
use Pimcore\Update;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunUpdateScriptCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('deployment:run-update-script')
            ->setDescription('Re-run an update script of a certain build')
            ->addArgument('buildNumber', InputArgument::REQUIRED, 'Build number of the script you want to run again');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $build = intval($input->getArgument('buildNumber'));

        $downloadUrl = 'https://' . Update::getUpdateHost() . '/v2/getUpdateFiles.php?for=' . $build;

        Update::downloadData($build, $downloadUrl);
        Update::executeScript($build, 'preupdate');
        Update::executeScript($build, 'postupdate');
        Update::cleanup();
    }
}
