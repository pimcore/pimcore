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

namespace Pimcore\Bundle\UuidBundle\Model\Tool\UUID\Listing;

use Exception;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID\Listing;
use Pimcore\Model;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of UUID for the specified parameters, returns an array of UUID elements
     *
     */
    public function load(): array
    {
        $items = $this->db->fetchFirstColumn('SELECT uuid FROM ' . UUID\Dao::TABLE_NAME .' '. $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        $result = [];
        foreach ($items as $uuid) {
            $result[] = UUID::getByUuid($uuid);
        }

        return $result;
    }

    /**
     * Returns the total amount of UUID entries
     *
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . UUID\Dao::TABLE_NAME .' ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
