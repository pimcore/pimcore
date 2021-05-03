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
use Pimcore\Config;
use Pimcore\Db\PhpArrayFileTable;

/**
 * @internal
 */
final class Version20210323082921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();
        $db->query('DROP TABLE IF EXISTS `website_settings`;');
        $db->query("CREATE TABLE `website_settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(190) NOT NULL DEFAULT '',
    `type` ENUM('text','document','asset','object','bool') DEFAULT NULL,
    `data` TEXT,
    `language` VARCHAR(15) NOT NULL DEFAULT '',
    `siteId` INT(11) UNSIGNED DEFAULT NULL,
    `creationDate` INT(11) UNSIGNED DEFAULT '0',
    `modificationDate` INT(11) UNSIGNED DEFAULT '0',
    PRIMARY KEY (`id`),
    INDEX `name` (`name`),
    INDEX `siteId` (`siteId`)
) DEFAULT CHARSET=utf8mb4;");

        // move data from PHP file to database
        $file = Config::locateConfigFile('website-settings.php');
        if (is_file($file)) {
            $data = PhpArrayFileTable::get($file)->fetchAll();

            foreach ($data as $row) {
                if (!empty($row['id'])) {
                    $db->insert('website_settings', $row);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `website_settings`;');
    }
}
