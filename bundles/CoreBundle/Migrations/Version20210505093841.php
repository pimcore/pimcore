<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;
use Pimcore\Db;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210505093841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'WebDAV locks with database backend instead of file-based';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS webdav_locks (
                id integer primary key NOT NULL,
                owner text,
                timeout integer,
                created integer,
                token text,
                scope integer,
                depth integer,
                uri text,
                INDEX idx_uri (uri)
            )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS webdav_locks');
    }
}
