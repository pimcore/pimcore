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

namespace Pimcore\Model\DataObject\QuantityValue\Unit\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\QuantityValue\Unit\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    public function load(): array
    {
        $units = [];

        $unitConfigs = $this->db->fetchAllAssociative('SELECT * FROM ' . DataObject\QuantityValue\Unit\Dao::TABLE_NAME .
            $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($unitConfigs as $unitConfig) {
            $unit = new DataObject\QuantityValue\Unit();
            $unit->setValues($unitConfig, true);
            $units[] = $unit;
        }

        $this->model->setUnits($units);

        return $units;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM '.DataObject\QuantityValue\Unit\Dao::TABLE_NAME.' ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
