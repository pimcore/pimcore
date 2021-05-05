<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210505093841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'WebDAV locks with SQLite instead of file-based';
    }

    public function up(Schema $schema): void
    {
        if(!file_exists(PIMCORE_SYSTEM_TEMP_DIRECTORY.'/webdav-locks.sqlite')) {
            try {
                $pdo = new \PDO('sqlite:'.PIMCORE_SYSTEM_TEMP_DIRECTORY.'/webdav-locks.sqlite');
                $pdo->exec('CREATE TABLE \'locks\' (
                    id integer primary key NOT NULL,
                    owner text,
                    timeout integer,
                    created integer,
                    token text,
                    scope integer,
                    depth integer,
                    uri text
                )');
                $pdo->exec('CREATE INDEX idx_uri ON \'locks\' (uri)');
            } catch (Exception $e) {
            }
        }
    }

    public function down(Schema $schema): void
    {
        @unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY.'/webdav-locks.sqlite');
    }
}
