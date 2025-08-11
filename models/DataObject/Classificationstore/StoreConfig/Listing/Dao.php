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
        $sql = 'SELECT id FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables());

        $configData = [];
        foreach ($configsData as $config) {
            $configData[] = DataObject\Classificationstore\StoreConfig::getById($config);
        }

        $this->model->setList($configData);

        return $configData;
    }

    public function getDataArray(): array
    {
        $configsData = $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $configsData;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\StoreConfig\Dao::TABLE_NAME_STORES . ' '. $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
