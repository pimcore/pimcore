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

final class Version20220718162200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve object url slugs loading performance';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('object_url_slugs')->hasIndex('fieldname_ownertype_position_objectId')) {
            $this->addSql('ALTER TABLE `object_url_slugs` ADD INDEX `fieldname_ownertype_position_objectId` (`fieldname`,`ownertype`,`position`,`objectId`)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('object_url_slugs')->hasIndex('fieldname_ownertype_position_objectId')) {
            $this->addSql('ALTER TABLE `object_url_slugs` DROP INDEX `fieldname_ownertype_position_objectId`');
        }
    }
}
