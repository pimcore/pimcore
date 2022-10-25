<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221019042134 extends AbstractMigration
{
    const TABLE_NAME = 'users_permission_definitions';
    const PERMISSION = 'plugins';

    public function getDescription(): string
    {
        return 'Remove plugin permission from  ' . self::TABLE_NAME . ' table!';
    }

    public function up(Schema $schema): void
    {
        $query = 'DELETE FROM %s WHERE `key` = \'%s\';';
        $this->addSql(sprintf($query, self::TABLE_NAME, self::PERMISSION));
    }

    public function down(Schema $schema): void
    {
        $query = 'INSERT INTO %s(`key`) VALUES (\'%s\')';
        $this->addSql(sprintf($query, self::TABLE_NAME, self::PERMISSION));
    }
}
