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

final class Version20220617145524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop custom_layouts table in favor of LocationAwareConfigRepository';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS custom_layouts');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `custom_layouts` (
                `id` varchar(64) NOT NULL,
                `classId` VARCHAR(50) NOT NULL,
                `name` VARCHAR(190) NULL DEFAULT NULL,
                `description` TEXT NULL,
                `creationDate` INT(11) UNSIGNED NULL DEFAULT NULL,
                `modificationDate` INT(11) UNSIGNED NULL DEFAULT NULL,
                `userOwner` INT(11) UNSIGNED NULL DEFAULT NULL,
                `userModification` INT(11) UNSIGNED NULL DEFAULT NULL,
                `default` tinyint(1) NOT NULL DEFAULT "0",
                PRIMARY KEY (`id`),
                UNIQUE INDEX `name` (`name`, `classId`)
            ) DEFAULT CHARSET=utf8mb4;');

        $this->write(sprintf('Please restore your custom layout data by running bin/console pimcore:deployment:custom-layouts-rebuild -c manually.'));
    }
}
