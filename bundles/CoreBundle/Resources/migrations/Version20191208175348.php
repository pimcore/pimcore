<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191208175348 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $objectsTable = $schema->getTable('objects');
        if($objectsTable->hasIndex('path')) {
            $objectsTable->dropIndex('path');
        }

        $assetsTable = $schema->getTable('assets');
        if($assetsTable->hasIndex('path')) {
            $assetsTable->dropIndex('path');
        }

        $assetsMetaTable = $schema->getTable('assets_metadata');
        if($assetsMetaTable->hasIndex('cid')) {
            $assetsMetaTable->dropIndex('cid');
        }

        $cacheTagsTable = $schema->getTable('cache_tags');
        if($cacheTagsTable->hasIndex('id')) {
            $cacheTagsTable->dropIndex('id');
        }

        $classificationstore_collectionrelationsTable = $schema->getTable('classificationstore_collectionrelations');
        if($classificationstore_collectionrelationsTable->hasIndex('colId')) {
            $classificationstore_collectionrelationsTable->dropIndex('colId');
        }

        $classificationstore_relationsTable = $schema->getTable('classificationstore_relations');
        if($classificationstore_relationsTable->hasIndex('groupId')) {
            $classificationstore_relationsTable->dropIndex('groupId');
        }

        $dependenciesTable = $schema->getTable('dependencies');
        if($dependenciesTable->hasIndex('sourcetype')) {
            $dependenciesTable->dropIndex('sourcetype');
        }

        $documentsTable = $schema->getTable('documents');
        if($documentsTable->hasIndex('path')) {
            $documentsTable->dropIndex('path');
        }

        $documents_elementsTable = $schema->getTable('documents_elements');
        if($documents_elementsTable->hasIndex('documentId')) {
            $documents_elementsTable->dropIndex('documentId');
        }

        $documents_translationsTable = $schema->getTable('documents_translations');
        if($documents_translationsTable->hasIndex('sourceId')) {
            $documents_translationsTable->dropIndex('sourceId');
        }

        $edit_lockTable = $schema->getTable('edit_lock');
        if($edit_lockTable->hasIndex('cid')) {
            $edit_lockTable->dropIndex('cid');
        }

        $gridconfig_favouritesTable = $schema->getTable('gridconfig_favourites');
        if($gridconfig_favouritesTable->hasIndex('ownerId')) {
            $gridconfig_favouritesTable->dropIndex('ownerId');
        }

        $gridconfig_sharesTable = $schema->getTable('gridconfig_shares');
        if($gridconfig_sharesTable->hasIndex('gridConfigId')) {
            $gridconfig_sharesTable->dropIndex('gridConfigId');
        }

        $importconfig_sharesTable = $schema->getTable('importconfig_shares');
        if($importconfig_sharesTable->hasIndex('data.sharedRoleIds')) {
            $importconfig_sharesTable->dropIndex('data.sharedRoleIds');
        }

        $propertiesTable = $schema->getTable('properties');
        if($propertiesTable->hasIndex('cid')) {
            $propertiesTable->dropIndex('cid');
        }

        $search_backend_dataTable = $schema->getTable('search_backend_data');
        if($search_backend_dataTable->hasIndex('id')) {
            $search_backend_dataTable->dropIndex('id');
        }

        $tags_assignmentTable = $schema->getTable('tags_assignment');
        if($tags_assignmentTable->hasIndex('tagid')) {
            $tags_assignmentTable->dropIndex('tagid');
        }

        $targeting_storageTable = $schema->getTable('targeting_storage');
        if($targeting_storageTable->hasIndex('targeting_storage_visitorId_index')) {
            $targeting_storageTable->dropIndex('targeting_storage_visitorId_index');
        }

        $translations_adminTable = $schema->getTable('translations_admin');
        if($translations_adminTable->hasIndex('key')) {
            $translations_adminTable->dropIndex('key');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $objectsTable = $schema->getTable('objects');
        if(!$objectsTable->hasIndex('path')) {
            $objectsTable->addIndex(['o_path'], 'path');
        }

        $assetsTable = $schema->getTable('assets');
        if(!$assetsTable->hasIndex('path')) {
            $assetsTable->addIndex(['path'], 'path');
        }

        $assetsMetaTable = $schema->getTable('assets_metadata');
        if(!$assetsMetaTable->hasIndex('cid')) {
            $assetsMetaTable->addIndex(['cid'], 'cid');
        }

        $cacheTagsTable = $schema->getTable('cache_tags');
        if(!$cacheTagsTable->hasIndex('id')) {
            $cacheTagsTable->addIndex(['id'], 'id');
        }

        $classificationstore_collectionrelationsTable = $schema->getTable('classificationstore_collectionrelations');
        if(!$classificationstore_collectionrelationsTable->hasIndex('colId')) {
            $classificationstore_collectionrelationsTable->addIndex(['colId'], 'colId');
        }

        $classificationstore_relationsTable = $schema->getTable('classificationstore_relations');
        if(!$classificationstore_relationsTable->hasIndex('groupId')) {
            $classificationstore_relationsTable->addIndex(['groupId'], 'groupId');
        }

        $dependenciesTable = $schema->getTable('dependencies');
        if(!$dependenciesTable->hasIndex('sourcetype')) {
            $dependenciesTable->addIndex(['sourcetype'], 'sourcetype');
        }

        $documentsTable = $schema->getTable('documents');
        if(!$documentsTable->hasIndex('path')) {
            $documentsTable->addIndex(['path'], 'path');
        }

        $documents_elementsTable = $schema->getTable('documents_elements');
        if(!$documents_elementsTable->hasIndex('documentId')) {
            $documents_elementsTable->addIndex(['documentId'], 'documentId');
        }

        $documents_translationsTable = $schema->getTable('documents_translations');
        if(!$documents_translationsTable->hasIndex('sourceId')) {
            $documents_translationsTable->addIndex(['sourceId'], 'sourceId');
        }

        $edit_lockTable = $schema->getTable('edit_lock');
        if(!$edit_lockTable->hasIndex('cid')) {
            $edit_lockTable->addIndex(['cid'], 'cid');
        }

        $gridconfig_favouritesTable = $schema->getTable('gridconfig_favourites');
        if(!$gridconfig_favouritesTable->hasIndex('ownerId')) {
            $gridconfig_favouritesTable->addIndex(['ownerId'], 'ownerId');
        }

        $importconfig_sharesTable = $schema->getTable('importconfig_shares');
        if(!$importconfig_sharesTable->hasIndex('data.sharedRoleIds')) {
            $importconfig_sharesTable->addIndex(['importConfigId'], 'data.sharedRoleIds');
        }

        $propertiesTable = $schema->getTable('properties');
        if(!$propertiesTable->hasIndex('cid')) {
            $propertiesTable->addIndex(['cid'], 'cid');
        }

        $search_backend_dataTable = $schema->getTable('search_backend_data');
        if(!$search_backend_dataTable->hasIndex('id')) {
            $search_backend_dataTable->addIndex(['id'], 'id');
        }

        $tags_assignmentTable = $schema->getTable('tags_assignment');
        if(!$tags_assignmentTable->hasIndex('tagid')) {
            $tags_assignmentTable->addIndex(['tagid'], 'tagid');
        }

        $targeting_storageTable = $schema->getTable('targeting_storage');
        if(!$targeting_storageTable->hasIndex('targeting_storage_visitorId_index')) {
            $targeting_storageTable->addIndex(['visitorId'], 'targeting_storage_visitorId_index');
        }

        $translations_adminTable = $schema->getTable('translations_admin');
        if(!$translations_adminTable->hasIndex('key')) {
            $translations_adminTable->addIndex(['key'], 'key');
        }
    }
}
