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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\QuantityValue\Unit\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @property \Pimcore\Model\Object\QuantityValue\Unit\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * @return array
     */
    public function load()
    {
        $units = [];

        $unitConfigs = $this->db->fetchAll("SELECT * FROM " . Object\QuantityValue\Unit\Dao::TABLE_NAME .
            $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($unitConfigs as $unitConfig) {
            $unit = new Object\QuantityValue\Unit();
            $unit->setValues($unitConfig);
            $units[] = $unit;
        }

        $this->model->setUnits($units);

        return $units;
    }

    /**
     * @return mixed
     */
    public function getTotalCount()
    {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . Object\QuantityValue\Unit\Dao::TABLE_NAME . "`" . $this->getCondition());

        return $amount["amount"];
    }
}
