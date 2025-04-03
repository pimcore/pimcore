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

namespace Pimcore\Db;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @internal
 */
final class ManagedTablesOnlyFilter
{
    private ManagerRegistry $registry;

    private ?array $managedTables = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(string $tableName): bool
    {
        if ($this->managedTables === null) {
            $this->loadManagedTables();
        }

        return in_array($tableName, $this->managedTables, true);
    }

    public function loadManagedTables(): void
    {
        $this->managedTables = ['migration_versions'];

        foreach ($this->registry->getManagers() as $name => $em) {
            if ($name !== 'default') {
                throw new RuntimeException('Only the default entity manager is supported. Found: ' . $name);
            }
            foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata instanceof ClassMetadata) {
                    $this->managedTables[] = $metadata->getTableName();
                }
            }
        }
        // Remove duplicates
        $this->managedTables = array_unique($this->managedTables);
    }
}
