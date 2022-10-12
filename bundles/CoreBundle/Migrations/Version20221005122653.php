<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221005122653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fulltext index to search_backend_data table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE search_backend_data DROP INDEX `fulltext`;");

        // This is because of a problem in mysql while trying to handle change of data type from varchar to text which is already indexed\
        // https://stackoverflow.com/questions/1827063/mysql-error-key-specification-without-a-key-length
        $this->addSql("ALTER TABLE search_backend_data DROP INDEX `fullpath`;");

        // Change the collation and character set of the column to be able to add fulltext search index
        $this->addSql("ALTER TABLE search_backend_data MODIFY COLUMN fullpath text;");
        $this->addSql("ALTER TABLE search_backend_data ADD FULLTEXT INDEX fulltext(`data`,`properties`, `fullpath`);");

        // Adding back the dropped index
        $this->addSql("ALTER TABLE search_backend_data ADD INDEX fullpath(`fullpath`);");

    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE search_backend_data DROP INDEX `fullpath`;");
        $this->addSql("ALTER TABLE search_backend_data DROP INDEX `fulltext`;");
        // Revert back the charset and collation info for the fullpath column
        $this->addSql("ALTER TABLE search_backend_data MODIFY COLUMN fullpath varchar(765);");
        $this->addSql("ALTER TABLE search_backend_data ADD FULLTEXT INDEX fulltext(`data`,`properties`)");
        $this->addSql("ALTER TABLE search_backend_data ADD INDEX fullpath(`fullpath`);");

    }
}
