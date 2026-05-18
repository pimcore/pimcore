<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use Exception;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:delete-store',
    description: 'Delete Classification Store by Store ID or delete all inactive Keys',
    aliases: ['classificationstore:delete-store']
)]
class DeleteClassificationStoreCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'storeId',
                InputArgument::OPTIONAL,
                'The specific store ID to delete'
            )
            ->addOption(
                'inactive-only',
                'i',
                InputOption::VALUE_NONE,
                'If set, deletes only inactive Keys (ignores provided Store ID).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = $input->getArgument('storeId');
        $inactiveOnly = $input->getOption('inactive-only');

        //if storeId and inactive only are both set, we delete only inactive store keys
        //kept as argument for BC purposes as it was mandatory to provide storeId before
        if ($inactiveOnly) {
            return $this->deleteInactive();
        } elseif ($storeId) {
            if (!is_numeric($storeId)) {
                throw new Exception('Invalid store ID');
            }

            return $this->deleteByStoreId((int)$storeId);
        } else {
            throw new Exception('Please provide a store ID or use the --inactive-only option');
        }
    }

    protected function deleteInactive(): int
    {
        $listing = new KeyConfig\Listing();
        $listing->setIncludeDisabled(true);
        foreach ($listing->load() as $keyConfig) {
            echo 'Deleting inactive store with ID ' . $keyConfig->getId() . "\n";
            $keyConfig->delete();
        }

        return 0;
    }

    protected function deleteByStoreId(int $storeId): int
    {
        $db = Db::get();

        $tableList = $db->fetchAllAssociative("SHOW TABLES LIKE 'object_classificationstore_data_%'");
        foreach ($tableList as $table) {
            $theTable = current($table);
            $sql = 'DELETE FROM ' . $theTable .
                ' WHERE keyId IN (SELECT id FROM classificationstore_keys WHERE storeId = ' . $storeId . ')';
            echo $sql . "\n";
            $db->executeQuery($sql);
        }

        $tableList = $db->fetchAllAssociative("SHOW TABLES LIKE 'object_classificationstore_groups_%'");

        foreach ($tableList as $table) {
            $theTable = current($table);
            $sql = 'DELETE FROM ' . $theTable .
                ' WHERE groupId IN (SELECT id FROM classificationstore_groups WHERE storeId = ' . $storeId . ')';
            echo $sql . "\n";
            $db->executeQuery($sql);
        }

        $sql = 'DELETE FROM classificationstore_keys WHERE storeId = ' . $storeId;
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'DELETE FROM classificationstore_groups WHERE storeId = ' . $storeId;
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'DELETE FROM classificationstore_collections WHERE storeId = ' . $storeId;
        echo $sql . "\n";
        $db->executeQuery($sql);

        $sql = 'DELETE FROM classificationstore_stores WHERE id = ' . $storeId;
        echo $sql . "\n";
        $db->executeQuery($sql);

        Cache::clearAll();

        return 0;
    }
}
