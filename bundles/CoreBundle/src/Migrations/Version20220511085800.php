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

final class Version20220511085800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add key and index to search_backend_data table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('search_backend_data')->hasColumn('key')) {
            $this->addSql('ALTER TABLE `search_backend_data`
                ADD COLUMN `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin default \'\',
                ADD INDEX `key` (`key`)
            ;');
        }
        if (!$schema->getTable('search_backend_data')->hasColumn('index')) {
            $this->addSql('ALTER TABLE `search_backend_data`
                ADD COLUMN `index` int(11) unsigned DEFAULT \'0\',
                ADD INDEX `index` (`index`)
            ;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('search_backend_data')->hasColumn('key')) {
            $this->addSql('ALTER TABLE `search_backend_data`
                DROP INDEX `key`,
                DROP COLUMN `key`
            ;');
        }
        if ($schema->getTable('search_backend_data')->hasColumn('index')) {
            $this->addSql('ALTER TABLE `search_backend_data`
                DROP INDEX `index`,
                DROP COLUMN `index`
            ;');
        }
    }
}
