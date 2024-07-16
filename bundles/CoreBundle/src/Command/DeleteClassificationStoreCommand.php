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

use Exception;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:delete-store',
    description: 'Delete Classification Store',
    aliases: ['classificationstore:delete-store']
)]
class DeleteClassificationStoreCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('storeId', InputArgument::REQUIRED, 'The store ID to delete')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = $input->getArgument('storeId');

        if (!is_numeric($storeId)) {
            throw new Exception('Invalid store ID');
        }

        $db = Db::get();

        $tableList = $db->fetchAllAssociative("show tables like 'object_classificationstore_data_%'");
        foreach ($tableList as $table) {
            $theTable = current($table);
            $sql = 'delete from ' . $theTable . ' where keyId In (select id from classificationstore_keys where storeId = ' . $db->quote($storeId) . ')';
            echo $sql . "\n";
            $db->executeQuery($sql);
        }

        $tableList = $db->fetchAllAssociative("show tables like 'object_classificationstore_groups_%'");
        foreach ($tableList as $table) {
            $theTable = current($table);
            $sql = 'delete from ' . $theTable . ' where groupId In (select id from classificationstore_groups where storeId = ' . $db->quote($storeId) . ')';
            echo $sql . "\n";
            $db->executeQuery($sql);
        }

        $sql = 'delete from classificationstore_keys where storeId = ' . $db->quote($storeId);
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'delete from classificationstore_groups where storeId = ' . $db->quote($storeId);
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'delete from classificationstore_collections where storeId = ' . $db->quote($storeId);
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'delete from classificationstore_stores where id = ' . $db->quote($storeId);
        echo $sql . "\n";
        $db->executeQuery($sql);

        Cache::clearAll();

        return 0;
    }
}
