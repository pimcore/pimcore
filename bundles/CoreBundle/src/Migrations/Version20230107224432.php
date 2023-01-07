<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230107224432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant XLIFF import/export permission to those who already had "translation" permission, due XLIFF functionalities being moved out to be Standalone from Core Translation';
    }

    public function up(Schema $schema): void
    {
        // Append to the comma separated list whenever the permissions text field has 'translation' but not already xliff_import_export
        $this->addSql(
            'UPDATE users SET permissions = CONCAT(permissions, \',xliff_import_export\')
            WHERE permissions LIKE \'%translation%\' AND permissions NOT LIKE \'%xliff_import_export%\''
        );
    }

    public function down(Schema $schema): void
    {
        // Replace to remove permission when the comma is suffixed (eg. first of the list or any order)
        $this->addSql('UPDATE users SET permissions = REPLACE(permissions, \'xliff_import_export,\', \'\')');
        // Replace to remove permission when the comma is prefixed (eg. last of the comma separated list)
        $this->addSql('UPDATE users SET permissions = REPLACE(permissions, \',xliff_import_export\', \'\')');
    }
}
