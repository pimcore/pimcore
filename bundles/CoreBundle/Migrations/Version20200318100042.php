<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200318100042 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        try {
            if (!$schema->getTable('objects')->hasIndex('o_classId')) {
                $this->addSql('ALTER TABLE `objects`
                        ADD INDEX `o_classId` (`o_classId`);');
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            if ($schema->getTable('objects')->hasIndex('o_classId')) {
                $this->addSql('ALTER TABLE `objects`
                    DROP INDEX `o_classId`
                    ');
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
