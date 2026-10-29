<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function getDataTableName(): string
    {
        return 'object_classificationstore_data_' . $this->model->getClass()->getId();
    }

    public function getGroupsTableName(): string
    {
        return 'object_classificationstore_groups_' . $this->model->getClass()->getId();
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $this->getEngine()->save($this->model);
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->getEngine()->delete($this->model);
    }

    /**
     * @throws Exception
     */
    public function load(): void
    {
        $this->getEngine()->load($this->model);
    }

    /**
     * Kept as the published facade of the model; the plural method is on the engine interface.
     *
     * @throws Exception
     */
    public function createUpdateTable(): void
    {
        $storeId = $this->getStoreId();
        $this->getEngineResolver()->resolveForStore($storeId)->createUpdateTables($this->model->getClass(), $storeId);
    }

    /**
     * @throws Exception
     */
    private function getEngine(): EngineInterface
    {
        return $this->getEngineResolver()->resolveForStore($this->getStoreId());
    }

    private function getEngineResolver(): EngineResolver
    {
        return Pimcore::getContainer()->get(EngineResolver::class);
    }

    /**
     * Null when no store is configured for the field, which means the default engine.
     */
    private function getStoreId(): ?int
    {
        // createUpdateTable() is reachable on a model that carries only its class, with no fieldname
        $fieldname = $this->model->getObjectVars()['fieldname'] ?? null;
        if (!is_string($fieldname)) {
            return null;
        }

        $fieldDefinitions = Service::getClassificationStoreFieldDefinitions(
            $this->model->getClass()->getFieldDefinitions()
        );
        $fieldDefinition = $fieldDefinitions[$fieldname] ?? null;

        return $fieldDefinition !== null ? Service::getConfiguredStoreId($fieldDefinition) : null;
    }
}
