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

namespace Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore sub group configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $configList = [];
        foreach ($configsData as $configData) {
            $subGroupConfig = new DataObject\Classificationstore\SubGroupConfig();
            $subGroupConfig->setValues($configData, true);
            $configList[] = $subGroupConfig;
        }

        $this->model->setList($configList);

        return $configList;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS . ' '. $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
