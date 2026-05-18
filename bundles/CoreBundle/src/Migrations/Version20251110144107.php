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

final class Version20251110144107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shareBetweenFolders column to gridconfigs table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('gridconfigs');
        if (!$table->hasColumn('shareBetweenFolders')) {
            $table->addColumn('shareBetweenFolders', 'boolean', ['notnull' => false]);
            $table->addIndex(['shareBetweenFolders'], 'shareBetweenFolders');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('gridconfigs');
        if ($table->hasColumn('shareBetweenFolders')) {
            $table->dropIndex('shareBetweenFolders');
            $table->dropColumn('shareBetweenFolders');
        }
    }
}
