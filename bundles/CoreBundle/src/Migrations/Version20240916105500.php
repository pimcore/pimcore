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

final class Version20240916105500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set default value 0 for creationDate and modificationDate columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `assets` SET `creationDate` = 0 WHERE `creationDate` IS NULL');
        $this->addSql('UPDATE `assets` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `assets` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');

        $this->addSql('UPDATE `assets_image_thumbnail_cache` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `assets_image_thumbnail_cache` CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');

        $this->addSql('UPDATE `documents` SET `creationDate` = 0 WHERE `creationDate` IS NULL');
        $this->addSql('UPDATE `documents` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `documents` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');

        $this->addSql('UPDATE `email_blocklist` SET `creationDate` = 0 WHERE `creationDate` IS NULL');
        $this->addSql('UPDATE `email_blocklist` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `email_blocklist` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');

        $this->addSql('UPDATE `objects` SET `creationDate` = 0 WHERE `creationDate` IS NULL');
        $this->addSql('UPDATE `objects` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `objects` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');

        $this->addSql('UPDATE `translations_messages` SET `creationDate` = 0 WHERE `creationDate` IS NULL');
        $this->addSql('UPDATE `translations_messages` SET `modificationDate` = 0 WHERE `modificationDate` IS NULL');
        $this->addSql('ALTER TABLE `translations_messages` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `assets` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL, CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `assets_image_thumbnail_cache` CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `documents` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL, CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `email_blocklist` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL, CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `objects` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL, CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `translations_messages` CHANGE `creationDate` `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL, CHANGE `modificationDate` `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT NULL');
    }
}
