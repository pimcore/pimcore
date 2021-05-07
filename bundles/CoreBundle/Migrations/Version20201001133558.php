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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20201001133558 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = \Pimcore\Db::get();

        // unfortunately we cannot use $schema->hasTable() here because of this bloody hardcoded piece of code here:
        // https://github.com/doctrine/DoctrineBundle/blob/1.12.x/DependencyInjection/Compiler/WellKnownSchemaFilterPass.php#L45
        $tables = $db->fetchCol('SHOW TABLES');
        if (!in_array('lock_keys', $tables)) {
            $this->addSql('CREATE TABLE `lock_keys` (
              `key_id` varchar(64) NOT NULL,
              `key_token` varchar(44) NOT NULL,
              `key_expiration` int(10) unsigned NOT NULL,
              PRIMARY KEY (`key_id`)
            ) DEFAULT CHARSET=utf8;');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `lock_keys`;');
    }
}
