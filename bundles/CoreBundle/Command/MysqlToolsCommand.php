<?php

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
use Pimcore\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // display error message
        if (!$input->getOption('mode')) {
            $this->writeError('Please specify the mode!');
            exit;
        }

        $db = \Pimcore\Db::get();

        if ($input->getOption('mode') == 'optimize') {
            $tables = $db->fetchAllAssociative('SHOW TABLES');

            foreach ($tables as $table) {
                $t = current($table);

                try {
                    Logger::debug('Running: OPTIMIZE TABLE ' . $t);
                    $db->executeQuery('OPTIMIZE TABLE ' . $t);
                } catch (\Exception $e) {
                    Logger::error((string) $e);
                }
            }
        } elseif ($input->getOption('mode') == 'warmup') {
            $tables = $db->fetchAllAssociative('SHOW TABLES');

            foreach ($tables as $table) {
                $t = current($table);

                try {
                    Logger::debug("Running: SELECT COUNT(*) FROM $t");
                    $res = $db->fetchOne("SELECT COUNT(*) FROM $t");
                    Logger::debug('Result: ' . $res);
                } catch (\Exception $e) {
                    Logger::error((string) $e);
                }
            }
        }

        return 0;
    }
}
