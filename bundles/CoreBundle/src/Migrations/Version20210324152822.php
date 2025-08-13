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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;

/**
 * @internal
 */
final class Version20210324152822 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAllAssociative("SHOW FULL TABLES WHERE `Tables_in_{$db->getDatabase()}` LIKE 'translations\_%' AND Table_type = 'BASE TABLE'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            $translationsTableSchema = $schema->getTable($translationsTable);

            if ($translationsTableSchema->hasColumn('key')
                && $translationsTableSchema->hasColumn('language')
                && $translationsTableSchema->hasColumn('text')
                && !$translationsTableSchema->hasColumn('type')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` ADD COLUMN `type` varchar(10) DEFAULT NULL AFTER `key`');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAllAssociative("SHOW FULL TABLES WHERE `Tables_in_{$db->getDatabase()}` LIKE 'translations\_%' AND Table_type = 'BASE TABLE'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            $translationsTableSchema = $schema->getTable($translationsTable);

            if ($translationsTableSchema->hasColumn('key')
                && $translationsTableSchema->hasColumn('language')
                && $translationsTableSchema->hasColumn('text')
                && $translationsTableSchema->hasColumn('type')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` DROP COLUMN `type`');
            }
        }
    }
}
