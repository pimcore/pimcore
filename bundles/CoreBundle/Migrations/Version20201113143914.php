<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20201113143914 extends AbstractPimcoreMigration
{
    private $tables = ['documents_email', 'documents_newsletter', 'documents_page',
        'documents_snippet', 'documents_printpage'];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach($this->tables as $table) {
            $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `action`;', $table));
            $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `module`;', $table));
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage(sprintf('Unable to rollback %s as the data was already deleted.', self::class));
        $this->writeMessage(sprintf('Please restore the data from tables %s manually from backup.', implode(',', $this->tables)));
    }
}
