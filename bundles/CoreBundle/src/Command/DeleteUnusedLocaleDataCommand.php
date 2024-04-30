<?php
declare(strict_types=1);

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
use Pimcore\Console\Traits\DryRun;
use Pimcore\Db;
use Pimcore\Tool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:locale:delete-unused-tables',
    description: 'Delete unused locale(invalid language) tables & views'
)]
class DeleteUnusedLocaleDataCommand extends AbstractCommand
{
    use DryRun;

    protected function configure(): void
    {
        $this
            ->addOption(
                'skip-locales',
                's',
                InputOption::VALUE_OPTIONAL,
                'Do not delete specified locale tables (comma separated eg.: en, en_AT)'
            )
        ;

        $this->configureDryRunOption('Just output the delete localized queries to be executed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        $tables = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_data\_%'");

        foreach ($tables as $table) {
            $printLine = false;
            $table = current($table);
            $classId = str_replace('object_localized_data_', '', $table);

            $result = $db->fetchAllAssociative('SELECT DISTINCT `language` FROM ' . $table . ' WHERE `language` NOT IN(' . implode(',', $languageList) .')');
            $result = ($result ? $result : []);

            //delete data from object_localized_data_classID tables
            foreach ($result as $res) {
                $language = $res['language'];
                if (!in_arrayi($language, $skipLocales) && !in_arrayi($language, $validLanguages)) {
                    $sqlDeleteData = 'Delete FROM object_localized_data_' . $classId  . ' WHERE `language` = ' . $db->quote($language);
                    $printLine = true;
                    if (!$this->isDryRun()) {
                        $output->writeln($sqlDeleteData);
                        $db->executeQuery($sqlDeleteData);
                    } else {
                        $output->writeln($this->dryRunMessage($sqlDeleteData));
                    }
                }
            }

            //drop unused localized view e.g. object_localized_classId_*
            $existingViews = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_{$classId}\_%'");
            foreach ($existingViews as $existingView) {
                $localizedView = current($existingView);
                $existingLanguage = str_replace('object_localized_'.$classId.'_', '', $localizedView);

                if (!in_arrayi($existingLanguage, $validLanguages)) {
                    $sqlDropView = 'DROP VIEW IF EXISTS object_localized_' . $classId . '_' .$existingLanguage;
                    $printLine = true;

                    if (!$this->isDryRun()) {
                        $output->writeln($sqlDropView);
                        $db->executeQuery($sqlDropView);
                    } else {
                        $output->writeln($this->dryRunMessage($sqlDropView));
                    }
                }
            }

            //drop unused localized table e.g. object_localized_query_classId_*
            $existingTables = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_query\_{$classId}\_%'");
            foreach ($existingTables as $existingTable) {
                $localizedTable = current($existingTable);
                $existingLanguage = str_replace('object_localized_query_'.$classId.'_', '', $localizedTable);

                if (!in_arrayi($existingLanguage, $validLanguages)) {
                    $sqlDropTable = 'DROP TABLE IF EXISTS object_localized_query_' . $classId . '_' .$existingLanguage;
                    $printLine = true;

                    if (!$this->isDryRun()) {
                        $output->writeln($sqlDropTable);
                        $db->executeQuery($sqlDropTable);
                    } else {
                        $output->writeln($this->dryRunMessage($sqlDropTable));
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
