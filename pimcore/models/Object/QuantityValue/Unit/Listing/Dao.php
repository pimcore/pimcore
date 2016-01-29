<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\QuantityValue\Unit\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * @return array
     */
    public function load()
    {
        $units = array();

        $unitIds = $this->db->fetchCol("SELECT id FROM " . Object\QuantityValue\Unit\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($unitIds as $id) {
            $units[] = Object\QuantityValue\Unit::getById($id);
        }

        $this->model->setUnits($units);

        return $units;
    }

    public function getTotalCount()
    {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . Object\QuantityValue\Unit\Dao::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }
}
