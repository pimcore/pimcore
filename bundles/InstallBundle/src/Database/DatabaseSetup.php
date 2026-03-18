<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\Database;

use Doctrine\DBAL\Connection;
use Pimcore\Db\Helper;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as DoctrineTransportConnection;

/**
 * Creates the core Pimcore database schema.
 *
 * Executes install.sql, creates infrastructure tables (cache + messenger),
 * and inserts required seed data (system user, root nodes, permissions).
 *
 * Does NOT create admin users or import data sources — those are
 * orchestration concerns handled by the Installer.
 *
 * @internal
 */
final class DatabaseSetup
{
    public function createSchema(Connection $db): void
    {
        $db->executeStatement('SET FOREIGN_KEY_CHECKS=0;');

        $this->executeSqlFile($db, __DIR__ . '/../../dump/install.sql');
        $this->createInfrastructureTables($db);

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');

        $this->insertSystemUser($db);
    }

    /**
     * Insert seed data required for a fresh Pimcore installation:
     * root asset/document/object nodes and default permissions.
     *
     * Skip this when a data source provides the complete dataset (e.g., SQL dumps
     * that already contain root nodes and permissions).
     */
    public function insertSeedData(Connection $db): void
    {
        $this->insertDatabaseContents($db);
    }

    private function executeSqlFile(Connection $db, string $filePath): void
    {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new \RuntimeException(sprintf('Could not read SQL file: %s', $filePath));
        }

        $db->executeStatement($sql);
    }

    private function createInfrastructureTables(Connection $db): void
    {
        $cacheAdapter = new DoctrineDbalAdapter($db);
        $cacheAdapter->createTable();

        $doctrineTransportConn = new DoctrineTransportConnection([], $db);
        $doctrineTransportConn->setup();
    }

    private function insertSystemUser(Connection $db): void
    {
        $db->insert('users', [
            'parentId' => 0,
            'name' => 'system',
            'admin' => 1,
            'active' => 1,
        ]);

        $db->update('users', ['id' => 0], ['name' => 'system', 'type' => 'user']);
        $db->executeStatement('ALTER TABLE users AUTO_INCREMENT = 1');
    }

    private function insertDatabaseContents(Connection $db): void
    {
        $this->insertRootAsset($db);
        $this->insertRootDocument($db);
        $this->insertRootObject($db);
        $this->insertDefaultPermissions($db);
    }

    private function insertRootAsset(Connection $db): void
    {
        $db->insert('assets', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'folder',
            'filename' => '',
            'path' => '/',
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
    }

    private function insertRootDocument(Connection $db): void
    {
        $db->insert('documents', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'page',
            'key' => '',
            'path' => '/',
            'index' => 999999,
            'published' => 1,
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));

        $db->insert('documents_page', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'controller' => 'App\\Controller\\DefaultController::defaultAction',
            'template' => '',
            'title' => '',
            'description' => '',
        ]));
    }

    private function insertRootObject(Connection $db): void
    {
        $db->insert('objects', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'folder',
            'key' => '',
            'path' => '/',
            'index' => 999999,
            'published' => 1,
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
    }

    private function insertDefaultPermissions(Connection $db): void
    {
        $userPermissions = [
            'assets', 'classes', 'selectoptions', 'clear_cache',
            'clear_fullpage_cache', 'clear_temp_files', 'dashboards',
            'document_types', 'documents', 'emails', 'notes_events',
            'objects', 'predefined_properties', 'asset_metadata',
            'recyclebin', 'redirects', 'seemode', 'share_configurations',
            'system_settings', 'tags_configuration', 'tags_assignment',
            'tags_search', 'thumbnails', 'translations', 'users',
            'website_settings', 'workflow_details', 'notifications',
            'notifications_send', 'sites', 'objects_sort_method',
            'objectbricks', 'fieldcollections', 'quantityValueUnits',
            'classificationstore',
        ];

        foreach ($userPermissions as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
