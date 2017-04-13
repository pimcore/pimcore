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
    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Schedule\Task elements
     *
     * @return array
     */
    public function load()
    {
        $versions = [];
        $data = $this->db->fetchCol('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $id) {
            $versions[] = Model\Version::getById($id);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM versions ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
