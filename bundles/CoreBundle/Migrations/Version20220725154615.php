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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220725154615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAllAssociative("SHOW FULL TABLES WHERE `Tables_in_{$db->getDatabase()}` LIKE 'translations\_%' AND Table_type = 'BASE TABLE'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            if (!$schema->getTable($translationsTable)->hasColumn('userOwner')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` ADD COLUMN `userOwner` int(11) unsigned DEFAULT NULL');
            }

            if (!$schema->getTable($translationsTable)->hasColumn('userModification')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` ADD COLUMN `userModification` int(11) unsigned DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAllAssociative("SHOW FULL TABLES WHERE `Tables_in_{$db->getDatabase()}` LIKE 'translations\_%' AND Table_type = 'BASE TABLE'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            if ($schema->getTable($translationsTable)->hasColumn('userOwner')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` DROP COLUMN `userOwner`');
            }

            if ($schema->getTable($translationsTable)->hasColumn('userModification')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` DROP COLUMN `userModification`');
            }
        }
    }
}
