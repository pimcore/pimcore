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

/**
 * @internal
 */
final class Version20201201084201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach (['cache', 'cache_tags'] as $tableName) {
            if ($schema->hasTable($tableName)) {
                $this->addSql(sprintf('DROP TABLE IF EXISTS `%s`;', $tableName));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `cache`;
        CREATE TABLE `cache` (
          `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
          `data` longblob,
          `mtime` INT(11) UNSIGNED DEFAULT NULL,
          `expire` INT(11) UNSIGNED DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8mb4;");

        $this->addSql("DROP TABLE IF EXISTS `cache_tags`;
        CREATE TABLE `cache_tags` (
          `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
          `tag` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
          PRIMARY KEY (`id`,`tag`),
          INDEX `tag` (`tag`)
        ) DEFAULT CHARSET=ascii;");
    }
}
