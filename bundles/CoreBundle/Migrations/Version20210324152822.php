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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAll("SHOW TABLES LIKE 'translations\_%'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            if (!$schema->getTable($translationsTable)->hasColumn('type')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` ADD COLUMN `type` varchar(10) DEFAULT NULL AFTER `key`');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $db = Db::get();

        $translationsTables = $db->fetchAll("SHOW TABLES LIKE 'translations\_%'");
        foreach ($translationsTables as $table) {
            $translationsTable = current($table);

            if ($schema->getTable($translationsTable)->hasColumn('type')) {
                $this->addSql('ALTER TABLE `'.$translationsTable.'` DROP COLUMN `type`');
            }
        }
    }
}
