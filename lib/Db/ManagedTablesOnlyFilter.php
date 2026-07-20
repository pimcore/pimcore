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

namespace Pimcore\Db;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Pimcore\Console\CommandContextHolder;
use RuntimeException;

/**
 * @internal
 */
final class ManagedTablesOnlyFilter
{
    private ManagerRegistry $registry;

    private CommandContextHolder $commandContextHolder;

    private ?array $managedTables = null;

    public function __construct(ManagerRegistry $registry, CommandContextHolder $commandContextHolder)
    {
        $this->registry = $registry;
        $this->commandContextHolder = $commandContextHolder;
    }

    public function __invoke(string $tableName): bool
    {
        if (!$this->isActiveForCommand()) {
            return true;
        }

        if ($this->managedTables === null) {
            $this->loadManagedTables();
        }

        return in_array($tableName, $this->managedTables, true);
    }

    private function loadManagedTables(): void
    {
        $this->managedTables = [];

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

    private function isActiveForCommand(): bool
    {
        return $this->commandContextHolder->getCommandName() === 'doctrine:schema:update';
    }
}
