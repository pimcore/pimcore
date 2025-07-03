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

final class Version20250703080911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add column "saveFilters" to "gridconfigs" table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('gridconfigs');
        if(!$table->hasColumn('saveFilters')) {
            $this->addSql('ALTER TABLE gridconfigs ADD saveFilters TINYINT(1) DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->createTable('gridconfigs');
        if ($table->hasColumn('saveFilters')) {
            $this->addSql('ALTER TABLE gridconfigs DROP saveFilters');
        }
    }
}
