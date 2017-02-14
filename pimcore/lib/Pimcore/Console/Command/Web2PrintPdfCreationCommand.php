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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Web2Print\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Web2PrintPdfCreationCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('web2print:pdf-creation')
            ->setDescription('Start pdf creation')
            ->addOption(
                'processId', 'p',
                InputOption::VALUE_REQUIRED,
                "process-id with pdf creation definitions"
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2048M');
        Processor::getInstance()->startPdfGeneration($input->getOption("processId"));
    }
}
