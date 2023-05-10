<?php

declare(strict_types=1);

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
