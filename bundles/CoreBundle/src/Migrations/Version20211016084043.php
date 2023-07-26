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

final class Version20211016084043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add setAsFavourite to gridconfigs to allow admins to set gridconfig as favorite for users';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('gridconfigs')->hasColumn('setAsFavourite')) {
            $this->addSql('ALTER TABLE gridconfigs ADD setAsFavourite TINYINT(1) NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('gridconfigs')->hasColumn('setAsFavourite')) {
            $this->addSql('ALTER TABLE gridconfigs DROP setAsFavourite');
        }
    }
}
