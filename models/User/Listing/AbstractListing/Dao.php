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

namespace Pimcore\Model\User\Listing\AbstractListing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\User\Listing\AbstractListing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of users for the specified parameters, returns an array of User elements
     *
     */
    public function load(): array
    {
        $items = [];
        $usersData = $this->db->fetchAllAssociative('SELECT id,type FROM users' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($usersData as $userData) {
            $className = Model\User\Service::getClassNameForType($userData['type']);
            $item = $className::getById($userData['id']);
            if ($item) {
                $items[] = $item;
            }
        }

        $this->model->setItems($items);

        return $items;
    }

    protected function getCondition(): string
    {
        $condition = parent::getCondition();
        if (!empty($condition)) {
            $condition .= ' AND ';
        } else {
            $condition = ' WHERE ';
        }

        $types = [$this->model->getType(), $this->model->getType() . 'folder'];
        $condition .= "id > 0 AND `type` IN ('" . implode("','", $types) . "')";

        return $condition;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
