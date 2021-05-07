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

class Version20190408084129 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `quantityvalue_units`
            CHANGE `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            CHANGE `baseunit` `baseunit` INT(11) UNSIGNED DEFAULT NULL,
            ADD `converter` VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE `quantityvalue_units`
            ADD CONSTRAINT `fk_baseunit`
            FOREIGN KEY (`baseunit`)
            REFERENCES `quantityvalue_units` (`id`)
            ON DELETE SET NULL
            ON UPDATE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `quantityvalue_units`
            DROP FOREIGN KEY `fk_baseunit`,
            DROP `converter`,
            CHANGE `baseunit` `baseunit` VARCHAR(10)');
    }
}
