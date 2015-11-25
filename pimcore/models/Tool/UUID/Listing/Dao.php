<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool\UUID\Listing;

use Pimcore\Model;
use Pimcore\Model\Tool\UUID;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     * @return array
     */
    public function load() {
        $items = $this->db->fetchCol("SELECT uuid FROM " . Resource::TABLE_NAME ." ". $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        $result = array();
        foreach ($items as $uuid) {
            $result[] = UUID::getByUuid($uuid);
        }

        return $result;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     * @return integer
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Resource::TABLE_NAME ." " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }
        return $amount;
    }
}
