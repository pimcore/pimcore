<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;

class Version20230424084415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove all "attributes" data from link editables';
    }

    public function up(Schema $schema): void
    {
        if($schema->hasTable('documents_editables')){
            $db = Db::get();
            $primaryKey = $schema->getTable('documents_editables')->getPrimaryKey()->getColumns();
            $editables = $db->fetchAllAssociative('SELECT * FROM documents_editables WHERE type = ?', ['link']);

            foreach ($editables as $editable) {
                $unserialized = unserialize($editable['data']);
                if(array_key_exists('attributes', $unserialized)){
                    unset($unserialized['attributes']);

                    $editable['data'] = serialize($unserialized);

                    Db\Helper::upsert(
                        $db,
                        'documents_editables',
                        $editable,
                        $primaryKey
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('Can\'t bring deleted data back ...');
    }
}
