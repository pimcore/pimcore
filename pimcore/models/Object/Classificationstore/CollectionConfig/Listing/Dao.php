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

namespace Pimcore\Model\Object\Classificationstore\CollectionConfig\Listing;

use Pimcore\Model;
//use Pimcore\Model\Object

/**
 * @property \Pimcore\Model\Object\Classificationstore\CollectionConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of Classificationstore collection configs for the specified parameters, returns an array of config elements
     *
     * @return array
     */
    public function load()
    {
        $sql = "SELECT id FROM " . \Pimcore\Model\Object\Classificationstore\CollectionConfig\Dao::TABLE_NAME_COLLECTIONS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchCol($sql, $this->model->getConditionVariables());

        $configData = [];
        foreach ($configsData as $config) {
            $configData[] = \Pimcore\Model\Object\Classificationstore\CollectionConfig::getById($config);
        }

        $this->model->setList($configData);

        return $configData;
    }

    /**
     * @return array
     */
    public function getDataArray()
    {
        $configsData = $this->db->fetchAll("SELECT * FROM " . \Pimcore\Model\Object\Classificationstore\CollectionConfig\Dao::TABLE_NAME_COLLECTIONS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $configsData;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . \Pimcore\Model\Object\Classificationstore\CollectionConfig\Dao::TABLE_NAME_COLLECTIONS . " ". $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
