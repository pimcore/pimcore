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

namespace Pimcore\Bundle\CoreBundle\Command\Database;

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends AbstractCommand
{
    protected static $defaultName = 'pimcore:database:export';
    protected $tableListStructureOnly = [
        'edit_lock',
        'lock_keys',
        'tree_locks',
        'webdav_locks',
        'tmp_store',
    ];

    protected function configure()
    {
        $this
            ->setDescription('Save the current state of the database into a file')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Name of the output file', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty(exec('mysqldump -V'))) {
            $output->writeln('mysqldump is not installed or not added to the $PATH');

            return self::FAILURE;
        }

        $db = Db::get();
        $config = $db->getParams();
        $dumpName = $input->getOption('output') ?: sprintf('%s_%s', $config['dbname'], time());

        $views = $db->fetchAllNumeric('SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_TYPE` LIKE "VIEW"', []);
        array_walk($views, function(&$view) use ($config) {
            $view = "--ignore-table={$config['dbname']}.".reset($view);
        });

        $cmd = "mysqldump -h {$config['host']} {$config['dbname']} -u {$config['user']} -p\"{$config['password']}\" --skip-triggers {implode(' ', $views)} -r $dumpName.sql > /dev/null 2>&1";

        exec($cmd);

        return self::SUCCESS;
    }
}
