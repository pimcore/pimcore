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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Db;

/**
 * Seeds a store with a group and a key of a given identifier, and removes them again.
 *
 * The AUTO_INCREMENT of the configuration tables is deliberately left raised rather than restored
 * to MAX(id) + 1: restoring it hands ids out that were already given out and deleted earlier in
 * the same process, and {@see \Pimcore\Model\DataObject\Classificationstore\Service::$definitionsCache}
 * is keyed by key id and evicted by neither a key save nor a key delete, so the next key to be
 * given that id is answered for by the definition of the one that held it before.
 *
 * @internal
 */
trait IdentifiedRowsFixtureTrait
{
    private array $createdStoreIds = [];

    protected function removeIdentifiedRows(): void
    {
        $db = Db::get();

        foreach ($this->createdStoreIds as $storeId) {
            foreach (['classificationstore_groups', 'classificationstore_keys'] as $table) {
                $db->delete($table, ['storeId' => $storeId]);
            }
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }

        $this->createdStoreIds = [];
    }

    protected function seedIdentifiedStore(): int
    {
        $db = Db::get();
        $db->insert('classificationstore_stores', ['name' => uniqid('identifiedstore', true)]);
        $storeId = (int) $db->lastInsertId();

        $this->createdStoreIds[] = $storeId;

        return $storeId;
    }

    protected function seedIdentifiedRow(string $table, int $storeId, string $name, ?string $identifier): int
    {
        $db = Db::get();
        $row = ['storeId' => $storeId, 'name' => $name, 'identifier' => $identifier];
        if ($table === 'classificationstore_keys') {
            $row += ['type' => 'input', 'enabled' => 1];
        }
        $db->insert($table, $row);

        return (int) $db->lastInsertId();
    }
}
