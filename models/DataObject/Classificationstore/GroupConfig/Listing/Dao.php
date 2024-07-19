<?php

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

namespace Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore group configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables());

        $configList = [];
        foreach ($configsData as $configData) {
            $groupConfig = new DataObject\Classificationstore\GroupConfig();
            $groupConfig->setValues($configData);
            $configList[] = $groupConfig;
        }

        $this->model->setList($configList);

        return $configList;
    }

    public function getDataArray(): array
    {
        $configsData = $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $configsData;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . ' '. $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
