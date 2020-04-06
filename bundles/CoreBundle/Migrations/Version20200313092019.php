<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Migration to adapt new base directory for App and CoreBundle migrations
 */
class Version20200313092019 extends AbstractPimcoreMigration
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
        // move app migrations to new path app/Resources/migrations -> app/Migrations
        $oldPath = PIMCORE_APP_ROOT.'/Resources/migrations/';
        $newPath = PIMCORE_APP_ROOT.'/Migrations/';

        if (!is_dir($newPath)) {
            File::mkdir($newPath);
        }

        $migrationFiles = glob($oldPath . 'Version*.php');
        if (is_array($migrationFiles) && !empty($migrationFiles)) {
            $this->writeMessage(sprintf('Moving custom migration scripts from %s to %s', $oldPath, $newPath));
            foreach ($migrationFiles as $migrationFile) {
                $newMigrationFile = str_replace($oldPath, $newPath, $migrationFile);
                File::rename($migrationFile, $newMigrationFile);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // move app migrations to old path app/Migrations -> app/Resources/migrations
        $newPath = PIMCORE_APP_ROOT.'/Migrations/';
        $oldPath = PIMCORE_APP_ROOT.'/Resources/migrations/';

        if (!is_dir($oldPath)) {
            File::mkdir($oldPath);
        }

        $migrationFiles = glob($newPath . 'Version*.php');
        if (is_array($migrationFiles) && !empty($migrationFiles)) {
            $this->writeMessage(sprintf('Moving custom migration scripts from %s to %s', $newPath, $oldPath));
            foreach ($migrationFiles as $migrationFile) {
                $newMigrationFile = str_replace($newPath, $oldPath, $migrationFile);
                File::rename($migrationFile, $newMigrationFile);
            }
        }
    }
}
