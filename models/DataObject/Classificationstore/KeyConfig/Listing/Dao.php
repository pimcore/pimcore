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

namespace Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Classificationstore key configs for the specified parameters, returns an array of config elements
     *
     */
    public function load(): array
    {
        $sql = 'SELECT * FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchAllAssociative($sql, $this->model->getConditionVariables());

        $configList = [];
        foreach ($configsData as $keyConfigData) {
            $keyConfig = new DataObject\Classificationstore\KeyConfig();
            $keyConfigData['enabled'] = (bool)$keyConfigData['enabled'];
            $keyConfig->setValues($keyConfigData);
            $configList[] = $keyConfig;
        }

        $this->model->setList($configList);

        return $configList;
    }

    public function getDataArray(): array
    {
        $configsData = $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $configsData;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . ' '. $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }

    protected function getCondition(): string
    {
        $condition = $this->model->getIncludeDisabled() ? '(enabled is null or enabled = 0)' : 'enabled = 1';

        $cond = $this->model->getCondition();
        if ($cond) {
            $condition = $condition . ' AND (' . $cond . ')';
        }

        return ' WHERE ' . $condition . ' ';
    }
}
