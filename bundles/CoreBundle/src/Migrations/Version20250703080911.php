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
use Pimcore\Db;

final class Version20250703080911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add column "saveFilters" to "gridconfigs" table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('gridconfigs');
        if(!$table->hasColumn('saveFilters')) {
            $this->addSql('ALTER TABLE gridconfigs ADD saveFilters TINYINT(1) DEFAULT 0 NOT NULL');
        }

        //detect if any filter was already set and set to saveFilters to true
        Db::get()->executeQuery("UPDATE gridconfigs SET saveFilters = 1 WHERE `config` LIKE '%filter\":[{\"%'");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('gridconfigs');
        if ($table->hasColumn('saveFilters')) {
            $this->addSql('ALTER TABLE gridconfigs DROP saveFilters');
        }
    }
}
