<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Listing\AbstractListing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of users for the specifies parameters, returns an array of User elements
     * @return array
     */
    public function load() {

        $items = array();
        $usersData = $this->db->fetchAll("SELECT id,type FROM users" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($usersData as $userData) {
            $className = Model\User\Service::getClassNameForType($userData["type"]);
            $item = $className::getById($userData["id"]);
            if($item) {
                $items[] = $item;
            }
        }

        $this->model->setItems($items);
        return $items;
    }

    /**
     * @return string
     */
    protected function getCondition() {
        $condition = parent::getCondition();
        if(!empty($condition)){
            $condition.=" AND ";
        } else {
            $condition = " WHERE ";
        }

        $types = array($this->model->getType(), $this->model->getType() . "folder");
        $condition .= "id > 0 AND `type` IN ('" . implode("','",$types) . "')";

        return $condition;
    }

}
