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

final class Version20230124120641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Covert column types to JSON for columns containing only JSON data';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('classificationstore_keys')->hasColumn('definition') === true) {
            $this->addSql('ALTER TABLE `classificationstore_keys` MODIFY COLUMN `definition` json;');
        }
        if ($schema->getTable('users')->hasColumn('keyBindings') === true) {
            $this->addSql('ALTER TABLE `users` MODIFY COLUMN `keyBindings` json;');
        }
        if ($schema->getTable('gridconfigs')->hasColumn('config') === true) {
            $this->addSql('ALTER TABLE `gridconfigs` MODIFY COLUMN `config` json;');
        }
        if ($schema->getTable('importconfigs')->hasColumn('config') === true) {
            $this->addSql('ALTER TABLE `importconfigs` MODIFY COLUMN `config` json;');
        }
        if ($schema->getTable('targeting_storage')->hasColumn('value') === true) {
            $this->addSql('ALTER TABLE `targeting_storage` MODIFY COLUMN `value` json;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('classificationstore_keys')->hasColumn('definition') === true) {
            $this->addSql('ALTER TABLE `classificationstore_keys` MODIFY COLUMN `definition` LONGTEXT;');
        }
        if ($schema->getTable('users')->hasColumn('keyBindings') === true) {
            $this->addSql('ALTER TABLE `users` MODIFY COLUMN `keyBindings` LONGTEXT;');
        }
        if ($schema->getTable('gridconfigs')->hasColumn('config') === true) {
            $this->addSql('ALTER TABLE `gridconfigs` MODIFY COLUMN `config` LONGTEXT;');
        }
        if ($schema->getTable('importconfigs')->hasColumn('config') === true) {
            $this->addSql('ALTER TABLE `importconfigs` MODIFY COLUMN `config` LONGTEXT;');
        }
        if ($schema->getTable('targeting_storage')->hasColumn('value') === true) {
            $this->addSql('ALTER TABLE `targeting_storage` MODIFY COLUMN `value` LONGTEXT;');
        }
    }
}
