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
use Pimcore\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MysqlToolsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:mysql-tools')
            ->setAliases(['mysql-tools'])
            ->setDescription('Optimize and warmup mysql database')
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_REQUIRED,
                'optimize or warmup'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // display error message
        if (!$input->getOption('mode')) {
            $this->writeError('Please specify the mode!');
            exit;
        }

        $db = \Pimcore\Db::get();

        if ($input->getOption('mode') == 'optimize') {
            $tables = $db->fetchAll('SHOW TABLES');

            foreach ($tables as $table) {
                $t = current($table);
                try {
                    Logger::debug('Running: OPTIMIZE TABLE ' . $t);
                    $db->query('OPTIMIZE TABLE ' . $t);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            }
        } elseif ($input->getOption('mode') == 'warmup') {
            $tables = $db->fetchAll('SHOW TABLES');

            foreach ($tables as $table) {
                $t = current($table);
                try {
                    Logger::debug("Running: SELECT COUNT(*) FROM $t");
                    $res = $db->fetchOne("SELECT COUNT(*) FROM $t");
                    Logger::debug('Result: ' . $res);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            }
        }
    }
}
