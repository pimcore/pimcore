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

class Version20190320133439 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `assets`
            CHANGE COLUMN `filename` `filename` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_bin' AFTER `type`,
            CHANGE COLUMN `path` `path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_bin' AFTER `filename`;");

        $this->addSql("ALTER TABLE `documents`
            CHANGE COLUMN `key` `key` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_bin' AFTER `type`,
            CHANGE COLUMN `path` `path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_bin' AFTER `key`;");

        $this->addSql("ALTER TABLE `objects`
            CHANGE COLUMN `o_key` `o_key` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_bin' AFTER `o_type`,
            CHANGE COLUMN `o_path` `o_path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_bin' AFTER `o_key`;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `assets`
            CHANGE COLUMN `filename` `filename` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_general_ci' AFTER `type`,
            CHANGE COLUMN `path` `path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `filename`;");

        $this->addSql("ALTER TABLE `documents`
            CHANGE COLUMN `key` `key` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_general_ci' AFTER `type`,
            CHANGE COLUMN `path` `path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `key`;");

        $this->addSql("ALTER TABLE `objects`
            CHANGE COLUMN `o_key` `o_key` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_general_ci' AFTER `o_type`,
            CHANGE COLUMN `o_path` `o_path` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `o_key`;");
    }
}
