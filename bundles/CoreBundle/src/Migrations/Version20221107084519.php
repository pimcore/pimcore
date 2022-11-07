<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221107084519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove index column from object_url_slugs table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs DROP INDEX `index`;');
        $this->addSql('ALTER TABLE object_url_slugs DROP `index`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE object_url_slugs ADD `index` int(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER `fieldname`;');
        $this->addSql('ALTER TABLE object_url_slugs ADD INDEX `index`(`index`);');
    }
}
