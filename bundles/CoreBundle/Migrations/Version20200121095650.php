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

class Version20200121095650 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE `object_url_slugs` (
          `objectId` INT(11) NOT NULL DEFAULT '0',
            `classId` VARCHAR(50) NOT NULL DEFAULT '0',
          `fieldname` VARCHAR(70) NOT NULL DEFAULT '0',
          `index` INT(11) UNSIGNED NOT NULL DEFAULT '0',
          `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
          `ownername` VARCHAR(70) NOT NULL DEFAULT '',
          `position` VARCHAR(70) NOT NULL DEFAULT '0',
          `slug` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
          `siteId` INT(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`slug`, `siteId`),
          INDEX `index` (`index`),
          INDEX `objectId` (`objectId`),
          INDEX `classId` (`classId`),
          INDEX `fieldname` (`fieldname`),
          INDEX `position` (`position`),
          INDEX `ownertype` (`ownertype`),
          INDEX `ownername` (`ownername`),
          INDEX `slug` (`slug`),
          INDEX `siteId` (`siteId`)
        ) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE `object_url_slugs`;');
    }
}
