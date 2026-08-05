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

namespace Pimcore\Model\DataObject\Classificationstore\StoreConfig\Listing;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\StoreConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore store configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $storesData = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        $configData = [];
        $modelFactory = Pimcore::getContainer()->get('pimcore.model.factory');

        foreach ($storesData as $storeData) {
            /** @var DataObject\Classificationstore\StoreConfig $store */
            $store = $modelFactory->build(DataObject\Classificationstore\StoreConfig::class);
            $store->getDao()->assignVariablesToModel($storeData);

            $configData[] = $store;
        }

        $this->model->setList($configData);

        return $configData;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES . ' '. $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
