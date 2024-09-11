<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
final class Installer extends SettingsStoreAwareInstaller
{
    public function __construct(
        private readonly Connection $db,
        BundleInterface $bundle,

    ) {
        parent::__construct($bundle);
    }

    public const USER_PERMISSIONS_CATEGORY = 'Pimcore Generic Execution Engine';

    protected const USER_PERMISSIONS = [
        PermissionConstants::GEE_JOB_RUN,
        PermissionConstants::GEE_SEE_ALL_JOB_RUNS,
    ];

    /**
     * @throws SchemaException|Exception
     */
    public function install(): void
    {
        $this->installBundle();
        parent::install();
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        $this->uninstallBundle();
        parent::uninstall();
    }

    /**
     * @throws SchemaException|Exception
     */
    private function installBundle(): void
    {
        $currentSchema = $this->db->createSchemaManager()->introspectSchema();

        $this->installJobRunTable($currentSchema);
        $this->installLogTable($currentSchema);
        $this->addUserPermission($currentSchema);
        $this->executeDiffSql($currentSchema);
    }

    /**
     * @throws Exception
     */
    private function uninstallBundle(): void
    {
        $currentSchema = $this->db->createSchemaManager()->introspectSchema();

        $this->executeDiffSql($currentSchema);
        $this->removeUserPermission($currentSchema);
        $this->removeLogTable($currentSchema);
        $this->removeJobRunTable($currentSchema);
    }

    /**
     * @throws SchemaException
     */
    private function installJobRunTable(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            $jobRunTable = $schema->createTable(TableConstants::JOB_RUN_TABLE);
            $jobRunTable->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $jobRunTable->addColumn('ownerId', 'integer', ['notnull' => false, 'unsigned' => true]);
            $jobRunTable->addColumn('state', 'string', ['notnull' => true, 'length' => 100]);
            $jobRunTable->addColumn('currentStep', 'integer', ['notnull' => false, 'unsigned' => true]);
            $jobRunTable->addColumn(
                'currentMessage',
                'text',
                [
                    'notnull' => false,
                    'length' => 65535,
                ]
            );
            $jobRunTable->addColumn('log', 'text', ['notnull' => false, 'length' => 65535]);
            $jobRunTable->addColumn(
                'serializedJob',
                'text',
                [
                    'notnull' => false,
                    'length' => 4294967295,
                ]
            );
            $jobRunTable->addColumn('context', 'text', ['notnull' => false, 'length' => 4294967295]);
            $jobRunTable->addColumn('creationDate', 'integer', ['notnull' => false]);
            $jobRunTable->addColumn('modificationDate', 'integer', ['notnull' => false]);
            $jobRunTable->addColumn('executionContext', 'string', [
                'notnull' => false,
                'length' => 255,
                'default' => JobRun::DEFAULT_EXECUTION_CONTEXT,
            ]);
            $jobRunTable->addColumn('totalElements', 'integer', [
                'notnull' => true,
                'unsigned' => true,
            ]);
            $jobRunTable->addColumn('processedElementsForStep', 'integer', [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $jobRunTable->addForeignKeyConstraint(
                'users',
                ['ownerId'],
                ['id'],
                ['onDelete' => 'SET NULL'],
                'fk_generic_job_execution_owner_users'
            );

            $jobRunTable->setPrimaryKey(['id']);
        }
    }

    /**
     * @throws SchemaException
     */
    private function installLogTable(Schema $schema): void
    {
        if ($schema->hasTable(TableConstants::ERROR_LOG_TABLE)) {
            return;
        }

        $logTable = $schema->createTable(TableConstants::ERROR_LOG_TABLE);
        $logTable->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
        ]);
        $logTable->addColumn('jobRunId', 'integer', ['notnull' => true, 'unsigned' => true]);
        $logTable->addColumn('stepNumber', 'integer', ['notnull' => true, 'unsigned' => true]);
        $logTable->addColumn('elementId', 'integer', ['notnull' => false, 'unsigned' => true]);
        $logTable->addColumn('errorMessage', 'text', ['notnull' => false, 'length' => 65535]);

        $logTable->addForeignKeyConstraint(
            TableConstants::JOB_RUN_TABLE,
            ['jobRunId'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_generic_job_execution_log_jobs'
        );

        $logTable->setPrimaryKey(['id']);
    }

    /**
     * @throws Exception
     */
    private function removeJobRunTable(Schema $schema): void
    {
        if ($schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            $this->db->executeStatement('DROP TABLE ' . TableConstants::JOB_RUN_TABLE);
        }
    }

    /**
     * @throws Exception
     */
    private function removeLogTable(Schema $schema): void
    {
        if ($schema->hasTable(TableConstants::ERROR_LOG_TABLE)) {
            $this->db->executeStatement('DROP TABLE ' . TableConstants::ERROR_LOG_TABLE);
        }
    }

    /**
     * @throws Exception
     */
    private function addUserPermission(Schema $schema): void
    {
        if ($schema->hasTable(TableConstants::USER_PERMISSION_DEF_TABLE)) {
            foreach (self::USER_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->insert(TableConstants::USER_PERMISSION_DEF_TABLE)
                    ->values([
                        $this->db->quoteIdentifier('key') => ':key',
                        $this->db->quoteIdentifier('category') => ':category',
                    ])
                    ->setParameters([
                        'key' => $permission,
                        'category' => self::USER_PERMISSIONS_CATEGORY,
                    ]);

                $queryBuilder->executeStatement();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function removeUserPermission(Schema $schema): void
    {
        if ($schema->hasTable(TableConstants::USER_PERMISSION_DEF_TABLE)) {
            foreach (self::USER_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->delete(TableConstants::USER_PERMISSION_DEF_TABLE)
                    ->where($this->db->quoteIdentifier('key') . ' = :key')
                    ->setParameter('key', $permission);

                $queryBuilder->executeStatement();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function executeDiffSql(Schema $newSchema): void
    {
        $currentSchema = $this->db->createSchemaManager()->introspectSchema();
        $schemaComparator = new Comparator($this->db->getDatabasePlatform());
        $schemaDiff = $schemaComparator->compareSchemas($currentSchema, $newSchema);
        $dbPlatform = $this->db->getDatabasePlatform();
        if (!$dbPlatform instanceof AbstractPlatform) {
            throw new InstallationException('Could not get database platform.');
        }

        $sqlStatements = $dbPlatform->getAlterSchemaSQL($schemaDiff);

        if (!empty($sqlStatements)) {
            $this->db->executeStatement(implode(';', $sqlStatements));
        }
    }
}
