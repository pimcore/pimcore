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

use Pimcore\Config;
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
            ->setName('pimcore:web2print:pdf-creation')
            ->setAliases(['web2print:pdf-creation'])
            ->setDescription('Start pdf creation')
            ->addOption(
                'processId',
                'p',
                InputOption::VALUE_REQUIRED,
                'process-id with pdf creation definitions'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check for memory limit
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit !== '-1') {
            $config = Config::getSystemConfiguration();
            $memoryLimitConfig = $config['documents']['web_to_print']['pdf_creation_php_memory_limit'] ?? 0;
            if (!empty($memoryLimitConfig) && filesize2bytes($memoryLimit . 'B') < filesize2bytes($memoryLimitConfig . 'B')) {
                $this->output->writeln("\n <info>Info: </info> PHP:memory_limit set to <comment>" . $memoryLimitConfig . "</comment> from config <comment>documents.web_to_print.pdf_creation_php_memory_limit</comment>\n");

                ini_set('memory_limit', $memoryLimitConfig);
            }
        }

        Processor::getInstance()->startPdfGeneration($input->getOption('processId'));

        return 0;
    }
}
