<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191208175348 extends AbstractPimcoreMigration
{
    private $tablesToProcess = [
        '%_localized_%' => 'ooo_id',
        'object_relations_%' => 'src_id',
        'object_metadata_%' => 'o_id',
        'object_classificationstore_data_%' => 'groupId',
        'object_collection_%' => 'o_id',
    ];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = \Pimcore\Db::get();

        $objectsTable = $schema->getTable('objects');
        if ($objectsTable->hasIndex('path')) {
            $objectsTable->dropIndex('path');
        }

        $assetsTable = $schema->getTable('assets');
        if ($assetsTable->hasIndex('path')) {
            $assetsTable->dropIndex('path');
        }

        $assetsMetaTable = $schema->getTable('assets_metadata');
        if ($assetsMetaTable->hasIndex('cid')) {
            $assetsMetaTable->dropIndex('cid');
        }

        $cacheTagsTable = $schema->getTable('cache_tags');
        if ($cacheTagsTable->hasIndex('id')) {
            $cacheTagsTable->dropIndex('id');
        }

        $classificationstore_collectionrelationsTable = $schema->getTable('classificationstore_collectionrelations');
        if ($classificationstore_collectionrelationsTable->hasIndex('colId')) {
            $classificationstore_collectionrelationsTable->dropIndex('colId');
        }

        $classificationstore_relationsTable = $schema->getTable('classificationstore_relations');
        if ($classificationstore_relationsTable->hasIndex('groupId')) {
            $classificationstore_relationsTable->dropIndex('groupId');
        }

        $dependenciesTable = $schema->getTable('dependencies');
        if ($dependenciesTable->hasIndex('sourcetype')) {
            $dependenciesTable->dropIndex('sourcetype');
        }

        $documentsTable = $schema->getTable('documents');
        if ($documentsTable->hasIndex('path')) {
            $documentsTable->dropIndex('path');
        }

        $documents_elementsTable = $schema->getTable('documents_elements');
        if ($documents_elementsTable->hasIndex('documentId')) {
            $documents_elementsTable->dropIndex('documentId');
        }

        $documents_translationsTable = $schema->getTable('documents_translations');
        if ($documents_translationsTable->hasIndex('sourceId')) {
            $documents_translationsTable->dropIndex('sourceId');
        }

        $edit_lockTable = $schema->getTable('edit_lock');
        if ($edit_lockTable->hasIndex('cid')) {
            $edit_lockTable->dropIndex('cid');
        }

        $gridconfig_favouritesTable = $schema->getTable('gridconfig_favourites');
        if ($gridconfig_favouritesTable->hasIndex('ownerId')) {
            $gridconfig_favouritesTable->dropIndex('ownerId');
        }

        $gridconfig_sharesTable = $schema->getTable('gridconfig_shares');
        if ($gridconfig_sharesTable->hasIndex('gridConfigId')) {
            $gridconfig_sharesTable->dropIndex('gridConfigId');
        }

        $importconfig_sharesTable = $schema->getTable('importconfig_shares');
        if ($importconfig_sharesTable->hasIndex('data.sharedRoleIds')) {
            $this->addSql('DROP INDEX `data.sharedRoleIds` ON importconfig_shares');
        }

        $propertiesTable = $schema->getTable('properties');
        if ($propertiesTable->hasIndex('cid')) {
            $propertiesTable->dropIndex('cid');
        }

        $search_backend_dataTable = $schema->getTable('search_backend_data');
        if ($search_backend_dataTable->hasIndex('id')) {
            $search_backend_dataTable->dropIndex('id');
        }

        $tags_assignmentTable = $schema->getTable('tags_assignment');
        if ($tags_assignmentTable->hasIndex('tagid')) {
            $tags_assignmentTable->dropIndex('tagid');
        }

        $targeting_storageTable = $schema->getTable('targeting_storage');
        if ($targeting_storageTable->hasIndex('targeting_storage_visitorId_index')) {
            $targeting_storageTable->dropIndex('targeting_storage_visitorId_index');
        }

        $translations_adminTable = $schema->getTable('translations_admin');
        if ($translations_adminTable->hasIndex('key')) {
            $translations_adminTable->dropIndex('key');
        }

        $translations_websiteTable = $schema->getTable('translations_website');
        if ($translations_websiteTable->hasIndex('key')) {
            $translations_websiteTable->dropIndex('key');
        }

        $tree_locksTable = $schema->getTable('tree_locks');
        if ($tree_locksTable->hasIndex('id')) {
            $tree_locksTable->dropIndex('id');
        }

        $users_workspaces_assetTable = $schema->getTable('users_workspaces_asset');
        if ($users_workspaces_assetTable->hasIndex('cid')) {
            $users_workspaces_assetTable->dropIndex('cid');
        }

        $users_workspaces_documentTable = $schema->getTable('users_workspaces_document');
        if ($users_workspaces_documentTable->hasIndex('cid')) {
            $users_workspaces_documentTable->dropIndex('cid');
        }

        $users_workspaces_objectTable = $schema->getTable('users_workspaces_object');
        if ($users_workspaces_objectTable->hasIndex('cid')) {
            $users_workspaces_objectTable->dropIndex('cid');
        }

        foreach ($this->tablesToProcess as $pattern => $indexToRemove) {
            $tableNames = $db->fetchCol(sprintf("SHOW TABLES LIKE '%s';", $pattern));
            foreach ($tableNames as $tableName) {
                if ($schema->hasTable($tableName)) {
                    $table = $schema->getTable($tableName);
                    if ($table->hasIndex($indexToRemove)) {
                        $table->dropIndex($indexToRemove);
                    }
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = \Pimcore\Db::get();

        $objectsTable = $schema->getTable('objects');
        if (!$objectsTable->hasIndex('path')) {
            $objectsTable->addIndex(['o_path'], 'path');
        }

        $assetsTable = $schema->getTable('assets');
        if (!$assetsTable->hasIndex('path')) {
            $assetsTable->addIndex(['path'], 'path');
        }

        $assetsMetaTable = $schema->getTable('assets_metadata');
        if (!$assetsMetaTable->hasIndex('cid')) {
            $assetsMetaTable->addIndex(['cid'], 'cid');
        }

        $cacheTagsTable = $schema->getTable('cache_tags');
        if (!$cacheTagsTable->hasIndex('id')) {
            $cacheTagsTable->addIndex(['id'], 'id');
        }

        $classificationstore_collectionrelationsTable = $schema->getTable('classificationstore_collectionrelations');
        if (!$classificationstore_collectionrelationsTable->hasIndex('colId')) {
            $classificationstore_collectionrelationsTable->addIndex(['colId'], 'colId');
        }

        $classificationstore_relationsTable = $schema->getTable('classificationstore_relations');
        if (!$classificationstore_relationsTable->hasIndex('groupId')) {
            $classificationstore_relationsTable->addIndex(['groupId'], 'groupId');
        }

        $dependenciesTable = $schema->getTable('dependencies');
        if (!$dependenciesTable->hasIndex('sourcetype')) {
            $dependenciesTable->addIndex(['sourcetype'], 'sourcetype');
        }

        $documentsTable = $schema->getTable('documents');
        if (!$documentsTable->hasIndex('path')) {
            $documentsTable->addIndex(['path'], 'path');
        }

        $documents_elementsTable = $schema->getTable('documents_elements');
        if (!$documents_elementsTable->hasIndex('documentId')) {
            $documents_elementsTable->addIndex(['documentId'], 'documentId');
        }

        $documents_translationsTable = $schema->getTable('documents_translations');
        if (!$documents_translationsTable->hasIndex('sourceId')) {
            $documents_translationsTable->addIndex(['sourceId'], 'sourceId');
        }

        $edit_lockTable = $schema->getTable('edit_lock');
        if (!$edit_lockTable->hasIndex('cid')) {
            $edit_lockTable->addIndex(['cid'], 'cid');
        }

        $gridconfig_favouritesTable = $schema->getTable('gridconfig_favourites');
        if (!$gridconfig_favouritesTable->hasIndex('ownerId')) {
            $gridconfig_favouritesTable->addIndex(['ownerId'], 'ownerId');
        }

        $importconfig_sharesTable = $schema->getTable('importconfig_shares');
        if (!$importconfig_sharesTable->hasIndex('sharedRoleIds')) {
            $importconfig_sharesTable->addIndex(['importConfigId'], 'sharedRoleIds');
        }

        $propertiesTable = $schema->getTable('properties');
        if (!$propertiesTable->hasIndex('cid')) {
            $propertiesTable->addIndex(['cid'], 'cid');
        }

        $search_backend_dataTable = $schema->getTable('search_backend_data');
        if (!$search_backend_dataTable->hasIndex('id')) {
            $search_backend_dataTable->addIndex(['id'], 'id');
        }

        $tags_assignmentTable = $schema->getTable('tags_assignment');
        if (!$tags_assignmentTable->hasIndex('tagid')) {
            $tags_assignmentTable->addIndex(['tagid'], 'tagid');
        }

        $targeting_storageTable = $schema->getTable('targeting_storage');
        if (!$targeting_storageTable->hasIndex('targeting_storage_visitorId_index')) {
            $targeting_storageTable->addIndex(['visitorId'], 'targeting_storage_visitorId_index');
        }

        $translations_adminTable = $schema->getTable('translations_admin');
        if (!$translations_adminTable->hasIndex('key')) {
            $translations_adminTable->addIndex(['key'], 'key');
        }

        $translations_websiteTable = $schema->getTable('translations_website');
        if (!$translations_websiteTable->hasIndex('key')) {
            $translations_websiteTable->addIndex(['key'], 'key');
        }

        $tree_locksTable = $schema->getTable('tree_locks');
        if (!$tree_locksTable->hasIndex('id')) {
            $tree_locksTable->addIndex(['id'], 'id');
        }

        $users_workspaces_assetTable = $schema->getTable('users_workspaces_asset');
        if (!$users_workspaces_assetTable->hasIndex('cid')) {
            $users_workspaces_assetTable->addIndex(['cid'], 'cid');
        }

        $users_workspaces_documentTable = $schema->getTable('users_workspaces_document');
        if (!$users_workspaces_documentTable->hasIndex('cid')) {
            $users_workspaces_documentTable->addIndex(['cid'], 'cid');
        }

        $users_workspaces_objectTable = $schema->getTable('users_workspaces_object');
        if (!$users_workspaces_objectTable->hasIndex('cid')) {
            $users_workspaces_objectTable->addIndex(['cid'], 'cid');
        }

        foreach ($this->tablesToProcess as $pattern => $indexToAdd) {
            $tableNames = $db->fetchCol(sprintf("SHOW TABLES LIKE '%s';", $pattern));
            foreach ($tableNames as $tableName) {
                if ($schema->hasTable($tableName)) {
                    $table = $schema->getTable($tableName);
                    if (!$table->hasIndex($indexToAdd) && $table->hasColumn($indexToAdd)) {
                        $table->addIndex([$indexToAdd], $indexToAdd);
                    }
                }
            }
        }
    }
}
