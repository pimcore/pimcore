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
use Pimcore\Console\Traits\DryRun;
use Pimcore\Db;
use Pimcore\Tool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteUnusedLocaleDataCommand extends AbstractCommand
{
    use DryRun;

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

        $this->configureDryRunOption('Just output the delete localized queries to be executed.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = Db::get();
        $skipLocales = [];
        if ($input->getOption('skip-locales')) {
            $skipLocales = explode(',', $input->getOption('skip-locales'));
        }

        $languageList = [];
        $validLanguages = Tool::getValidLanguages();
        foreach ($validLanguages as $language) {
            $languageList[] = $db->quote($language);
        }

        $tables = $db->fetchAll("SHOW TABLES LIKE 'object\_localized\_data\_%'");

        foreach ($tables as $table) {
            $printLine = false;
            $table = current($table);
            $classId = str_replace('object_localized_data_', '', $table);

            $result = $db->fetchAll('SELECT DISTINCT `language` FROM ' . $table . ' WHERE `language` NOT IN(' . implode(',', $languageList) .')');
            $result = ($result ? $result : []);

            //delete data from object_localized_data_classID tables
            foreach ($result as $res) {
                $language = $res['language'];
                if (!in_array($language, $skipLocales) && !in_array($language, $validLanguages)) {
                    $sqlDeleteData = 'Delete FROM object_localized_data_' . $classId  . ' WHERE `language` = ' . $db->quote($language);
                    $printLine = true;
                    if (!$this->isDryRun()) {
                        $output->writeln($sqlDeleteData);
                        $db->query($sqlDeleteData);
                    } else {
                        $output->writeln($this->dryRunMessage($sqlDeleteData));
                    }
                }
            }

            //drop unused localized view e.g. object_localized_classId_*
            $existingViews = $db->fetchAll("SHOW TABLES LIKE 'object\_localized\_{$classId}\_%'");

            if (is_array($existingViews)) {
                foreach ($existingViews as $existingView) {
                    $localizedView = current($existingView);
                    $existingLanguage = str_replace('object_localized_'.$classId.'_', '', $localizedView);

                    if (!in_array($existingLanguage, $validLanguages)) {
                        $sqlDropView = 'DROP VIEW IF EXISTS object_localized_' . $classId . '_' .$existingLanguage;
                        $printLine = true;

                        if (!$this->isDryRun()) {
                            $output->writeln($sqlDropView);
                            $db->query($sqlDropView);
                        } else {
                            $output->writeln($this->dryRunMessage($sqlDropView));
                        }
                    }
                }
            }

            //drop unused localized table e.g. object_localized_query_classId_*
            $existingTables = $db->fetchAll("SHOW TABLES LIKE 'object\_localized\_query\_{$classId}\_%'");
            if (is_array($existingTables)) {
                foreach ($existingTables as $existingTable) {
                    $localizedTable = current($existingTable);
                    $existingLanguage = str_replace('object_localized_query_'.$classId.'_', '', $localizedTable);

                    if (!in_array($existingLanguage, $validLanguages)) {
                        $sqlDropTable = 'DROP TABLE IF EXISTS object_localized_query_' . $classId . '_' .$existingLanguage;
                        $printLine = true;

                        if (!$this->isDryRun()) {
                            $output->writeln($sqlDropTable);
                            $db->query($sqlDropTable);
                        } else {
                            $output->writeln($this->dryRunMessage($sqlDropTable));
                        }
                    }
                }
            }

            if ($printLine == true) {
                $output->writeln('------------');
            }
        }

        return 0;
    }
}
