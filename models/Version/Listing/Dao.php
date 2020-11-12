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
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Version\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Version\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    public function getCondition()
    {
        $condition = parent::getCondition();
        if($this->model->getLoadDrafts() == false){
            if(trim($condition)){
                $condition .=' AND draft = 0';
            }else{
                $condition = ' WHERE draft = 0';
            }
        }

        return $condition;
    }

    /**
     * Loads a list of versions for the specicified parameters, returns an array of Version elements
     *
     * @return array
     */
    public function load()
    {
        $versions = [];
        $data = $this->loadIdList();

        foreach ($data as $id) {
            $versions[] = Model\Version::getById($id);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return array
     */
    public function loadIdList(){
        return (array)$this->db->fetchCol('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM versions ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
