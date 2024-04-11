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

namespace Pimcore\Bundle\JobExecutionEngineBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Utils\Constants\PermissionConstants;
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

    public const USER_PERMISSIONS_CATEGORY = 'Pimcore Job Execution Engine';

    public const USER_PERMISSION_DEF_TABLE = 'users_permission_definitions';

    protected const USER_PERMISSIONS = [
        PermissionConstants::PJEE_JOB_RUN,
        PermissionConstants::PJEE_SEE_ALL_JOB_RUNS
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
        $this->removeJobRunTable($currentSchema);
    }

    /**
     * @throws SchemaException
     */
    private function installJobRunTable(Schema $schema): void
    {
        if (!$schema->hasTable(JobRun::TABLE)) {
            $jobRunTable = $schema->createTable(JobRun::TABLE);
            $jobRunTable->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $jobRunTable->addColumn('ownerId', 'integer', ['notnull' => false, 'unsigned' => true]);
            $jobRunTable->addColumn('state', 'string', ['notnull' => true, 'length' => 10]);
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

            $jobRunTable->addForeignKeyConstraint(
                'users',
                ['ownerId'],
                ['id'],
                ['onDelete' => 'SET NULL'],
                'fk_job_execution_owner_users'
            );

            $jobRunTable->setPrimaryKey(['id']);
        }
    }

    /**
     * @throws Exception
     */
    private function removeJobRunTable(Schema $schema): void
    {
        if ($schema->hasTable(JobRun::TABLE)) {
            $this->db->executeStatement('DROP TABLE ' . JobRun::TABLE);
        }
    }

    /**
     * @throws Exception
     */
    private function addUserPermission(Schema $schema): void
    {
        if ($schema->hasTable(self::USER_PERMISSION_DEF_TABLE)) {
            foreach (self::USER_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->insert(self::USER_PERMISSION_DEF_TABLE)
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
        if ($schema->hasTable(self::USER_PERMISSION_DEF_TABLE)) {
            foreach (self::USER_PERMISSIONS as $permission) {
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder
                    ->delete(self::USER_PERMISSION_DEF_TABLE)
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
