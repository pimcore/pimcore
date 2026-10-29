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

namespace Pimcore\Model\DataObject\Classificationstore\Engine;

use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;

/**
 * Storage backend for the values of a classification store.
 *
 * `@internal` is a staging decision, not a judgement that engines must be Pimcore's alone: the
 * interface is still expected to gain methods while the remaining engines are built, and every one
 * of those would be a BC break for a project implementation. Publishing it is a separate decision,
 * to be taken once it has stopped moving.
 *
 * @internal
 */
interface EngineInterface
{
    /**
     * @throws Exception
     */
    public function load(Classificationstore $classificationStore): void;

    /**
     * @throws Exception
     */
    public function save(Classificationstore $classificationStore): void;

    public function delete(Classificationstore $classificationStore): void;

    /**
     * $storeId is null when the field definition carries no store, which means the default store.
     */
    public function createUpdateTables(ClassDefinition $class, ?int $storeId): void;

    public function dropTables(ClassDefinition $class): void;

    /**
     * Removes the values of fields that no longer exist on their class definition.
     */
    public function cleanup(): void;

    /**
     * Removes every value belonging to the given store, across all classes.
     *
     * Called while the configuration of the store is still present, as an engine has to read the
     * key, group and collection tables to find its rows.
     *
     * This is the explicit removal path, not the whole of it: an engine whose storage hangs off the
     * `objects` or `classificationstore_groups` rows by a cascading foreign key - the EAV one does -
     * also loses values when an object or a group configuration is deleted, with no PHP involved.
     */
    public function deleteStoreValues(int $storeId): void;

    public function supports(EngineFeature $feature): bool;
}
