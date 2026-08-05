<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Config;

/**
 * @internal
 */
final class Version20210323082921 extends AbstractMigration
{
    protected function load(string $filePath): array
    {
        if (file_exists($filePath)) {
            $data = include($filePath);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();
        $db->executeQuery('DROP TABLE IF EXISTS `website_settings`;');
        $db->executeQuery("CREATE TABLE `website_settings` (
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
            $data = $this->load($file);
            foreach ($data as $row) {
                if (!empty($row['id'])) {
                    if (!isset($row['language'])) {
                        $row['language'] = '';
                        $this->write("Language of setting id {$row['id']}: {$row['name']} for siteId {$row['siteId']} was NULL, converted to empty string");
                    }

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
