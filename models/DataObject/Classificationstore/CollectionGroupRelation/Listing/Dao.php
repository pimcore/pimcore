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

namespace Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore group configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $condition = $this->getCondition();
        if ($condition) {
            $condition = $condition . ' AND ';
        } else {
            $condition = ' where ';
        }
        $condition .= DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS
            . '.groupId = ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.id';

        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS
            . ',' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS
            . $condition . $this->getOrder() . $this->getOffsetLimit();

        $data = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $configData = [];
        foreach ($data as $dataItem) {
            $entry = new DataObject\Classificationstore\CollectionGroupRelation();
            $resource = $entry->getDao();
            $resource->assignVariablesToModel($dataItem);

            $configData[] = $entry;
        }

        $this->model->setList($configData);

        return $configData;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\CollectionGroupRelation\Dao::TABLE_NAME_RELATIONS . ' '. $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
