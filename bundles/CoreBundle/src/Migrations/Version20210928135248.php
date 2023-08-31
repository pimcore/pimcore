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

final class Version20210928135248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if($schema->hasTable('sanitycheck')) {
            $schema->dropTable('sanitycheck');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS `sanitycheck` (
          `id` int(11) unsigned NOT NULL,
          `type` enum('document','asset','object') NOT NULL,
          PRIMARY KEY  (`id`,`type`)
        ) DEFAULT CHARSET=utf8mb4;");
    }
}
