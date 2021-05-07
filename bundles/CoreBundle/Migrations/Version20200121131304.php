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

class Version20200121131304 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_email` ADD COLUMN `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL;');
        $this->addSql('ALTER TABLE `documents_newsletter` ADD COLUMN `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL;');
        $this->addSql('ALTER TABLE `documents_page` ADD COLUMN `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL;');
        $this->addSql('ALTER TABLE `documents_printpage` ADD COLUMN `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL;');
        $this->addSql('ALTER TABLE `documents_snippet` ADD COLUMN `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_email` DROP COLUMN `missingRequiredEditable`;');
        $this->addSql('ALTER TABLE `documents_newsletter` DROP COLUMN `missingRequiredEditable`;');
        $this->addSql('ALTER TABLE `documents_page` DROP COLUMN `missingRequiredEditable`;');
        $this->addSql('ALTER TABLE `documents_printpage` DROP COLUMN `missingRequiredEditable`;');
        $this->addSql('ALTER TABLE `documents_snippet` DROP COLUMN `missingRequiredEditable`;');
    }
}
