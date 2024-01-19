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

final class Version20230428112302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate notes table schema to have a auto increment column';
    }

    public function up(Schema $schema): void
    {
        $notesData = $schema->getTable('notes_data');

        if (!$notesData->hasColumn('auto_id')) {
            $notesData->addColumn('auto_id', 'integer', [
                'autoincrement' => true,
            ]);

            $notesData->dropPrimaryKey();
            $notesData->setPrimaryKey(['auto_id']);
            $notesData->addUniqueIndex(['id', 'name'], 'UNIQ_E5A8E5E2BF3967505E237E06');
        }
    }

    public function down(Schema $schema): void
    {
        $notesData = $schema->getTable('notes_data');

        if ($notesData->hasColumn('auto_id')) {
            $notesData->dropPrimaryKey();
            $notesData->dropColumn('auto_id');
            $notesData->setPrimaryKey(['id', 'name']);
            $notesData->dropIndex('UNIQ_E5A8E5E2BF3967505E237E06');
        }
    }
}
