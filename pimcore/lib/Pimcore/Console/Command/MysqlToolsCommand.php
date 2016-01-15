<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MysqlToolsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('mysql-tools')
            ->setDescription('Optimize and warmup mysql database')
            ->addOption(
                'mode', 'm',
                InputOption::VALUE_REQUIRED,
                "optimize or warmup"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // display error message
        if(!$input->getOption("mode")) {
            $this->writeError("Please specify the mode!");
            exit;
        }

        $db = \Pimcore\Db::get();

        if($input->getOption("mode") == "optimize") {
            $tables = $db->fetchAll("SHOW TABLES");

            foreach ($tables as $table) {
                $t = current($table);
                try {
                    \Logger::debug("Running: OPTIMIZE TABLE " . $t);
                    $db->query("OPTIMIZE TABLE " . $t);
                } catch (Exception $e) {
                    \Logger::error($e);
                }
            }
        } else if ($input->getOption("mode") == "warmup") {
            $tables = $db->fetchAll("SHOW TABLES");

            foreach ($tables as $table) {
                $t = current($table);
                try {
                    \Logger::debug("Running: SELECT COUNT(*) FROM $t");
                    $res = $db->fetchOne("SELECT COUNT(*) FROM $t");
                    \Logger::debug("Result: " . $res);
                } catch (Exception $e) {
                    \Logger::error($e);
                }
            }
        }
    }
}
