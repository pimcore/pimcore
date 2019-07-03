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
use Pimcore\Tool;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteUnusedLocaleDataCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:locale:delete-unused-tables')
            ->setDescription('Delete unused locale(invalid language) tables & views')
            ->addOption(
                'skip-locales',
                's',
                InputOption::VALUE_OPTIONAL,
                'Do not delete specified locale tables (comma separated eg.: en, en_AT)'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $skipLocales = [];
        if ($input->getOption('skip-locales')) {
            $skipLocales = explode(',', $input->getOption('skip-locales'));
        }

        $validLanguages = Tool::getValidLanguages();

        $db = Db::get();

        $tables = $db->fetchAll("SHOW FULL TABLES LIKE '%object_localized_%'");

        foreach ($tables as $table) {
            $table = array_values($table);

            if (preg_match('/^object_localized_[0-9]+_/', $table[0]) ||
                preg_match('/^object_localized_query_[0-9]+_/', $table[0])) {

                $language = preg_replace(['/^object_localized_[0-9]+_/', '/^object_localized_query_[0-9]+_/'], '', $table[0]);
                $type = $table[1];

                if (!in_array($language, $skipLocales) && !in_array($language, $validLanguages)) {
                    $sql = ($type == 'VIEW' ? 'DROP VIEW ' : 'DROP TABLE ') . $db->quoteIdentifier($table[0]);
                    echo $sql . "\n";
                    $db->query($sql); //delete unused language table/view
                }
            }
        }
    }
}
