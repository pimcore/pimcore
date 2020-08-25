<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Listing\AbstractListing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\User\Listing\AbstractListing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of users for the specified parameters, returns an array of User elements
     *
     * @return array
     */
    public function load()
    {
        $items = [];
        $usersData = $this->db->fetchAll('SELECT id,type FROM users' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

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

    /**
     * @return string
     */
    protected function getCondition()
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

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
