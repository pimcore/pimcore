<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Class Version20200828091305
 *
 * Migrates tables from tinyint(4) to tinyint(1) for boolean-type content
 */
class Version20200828091305 extends AbstractPimcoreMigration
{
    /**
     * {@inheritDoc}
     */
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * @param Schema $schema
     *
     * @throws ConnectionException|DBALException
     */
    public function up(Schema $schema)
    {
        $tinyint = 'tinyint(1)';
        $db = \Pimcore\Db::get();

        $this->writeMessage("Migrating tables to {$tinyint}");
        $this->addSql("ALTER TABLE `custom_layouts` CHANGE `default` `default` {$tinyint} NOT NULL default '0';");
        $this->addSql('ALTER TABLE `' . ApplicationLoggerDb::TABLE_NAME . "` CHANGE `maintenanceChecked` `maintenanceChecked` {$tinyint} DEFAULT NULL;");

        $archiveTables = $db->query("SHOW TABLES LIKE '" . ApplicationLoggerDb::TABLE_NAME . "_%'")->fetchAll(\Doctrine\DBAL\FetchMode::COLUMN);
        if (!empty($archiveTables)) {
            foreach ($archiveTables as $at) {
                $this->addSql('ALTER TABLE ' . $db->quoteTableAs($at) . " MODIFY `maintenanceChecked` {$tinyint};");
            }
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws ConnectionException|DBALException
     */
    public function down(Schema $schema)
    {
        $tinyint = 'tinyint(4)';
        $db = \Pimcore\Db::get();

        $this->writeMessage("Restoring tables to {$tinyint}");
        $this->addSql("ALTER TABLE `custom_layouts` CHANGE `default` `default` {$tinyint} NOT NULL default '0';");
        $this->addSql('ALTER TABLE `' . ApplicationLoggerDb::TABLE_NAME . "` CHANGE `maintenanceChecked` `maintenanceChecked` {$tinyint} DEFAULT NULL;");

        $archiveTables = $db->query("SHOW TABLES LIKE '" . ApplicationLoggerDb::TABLE_NAME . "_%'")->fetchAll(\Doctrine\DBAL\FetchMode::COLUMN);
        if (!empty($archiveTables)) {
            foreach ($archiveTables as $at) {
                $this->addSql('ALTER TABLE ' . $db->quoteTableAs($at) . " MODIFY `maintenanceChecked` {$tinyint};");
            }
        }
    }
}
