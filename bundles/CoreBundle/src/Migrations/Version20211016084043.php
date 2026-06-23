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
