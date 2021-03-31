<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;

/**
 * @internal
 */
final class Version20210324152822 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        try {
            $db = Db::get();

            $translationsTables = $db->fetchAll("SHOW TABLES LIKE 'translations\_%'");
            foreach ($translationsTables as $table) {
                $translationsTable = current($table);

                if (!$schema->getTable($translationsTable)->hasColumn('type')) {
                    $this->addSql('ALTER TABLE `'.$translationsTable.'` ADD COLUMN `type` varchar(10) DEFAULT NULL AFTER `key`');
                }
            }
        } catch (\Exception $e) {
            $this->write('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        try {
            $db = Db::get();

            $translationsTables = $db->fetchAll("SHOW TABLES LIKE 'translations\_%'");
            foreach ($translationsTables as $table) {
                $translationsTable = current($table);

                if ($schema->getTable($translationsTable)->hasColumn('type')) {
                    $this->addSql('ALTER TABLE `'.$translationsTable.'` DROP COLUMN `type`');
                }
            }
        } catch (\Exception $e) {
            $this->write('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
