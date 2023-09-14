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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230914085710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change some columns of search_backend_table to non nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `fullpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL');
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL');
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `subtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `fullpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE `search_backend_data` MODIFY `subtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL');
    }
}
