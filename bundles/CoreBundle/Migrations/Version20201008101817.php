<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201008101817 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('documents_elements')) {
            $this->addSql('RENAME TABLE documents_elements TO documents_editables;');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE documents_editables TO documents_elements;');
    }
}
