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

namespace Pimcore\Model\ImportConfig\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\ImportConfig\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of importconfigs for the specicified parameters, returns an array of ImportConfig elements
     *
     * @return array
     */
    public function load()
    {
        $importconfigs = [];
        $data = $this->db->fetchAll('SELECT * FROM importconfigs' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $configData) {
            $importconfig = new Model\ImportConfig();
            $importconfig->setValues($configData);
            $importconfigs[] = $importconfig;
        }

        $this->model->setImportconfigs($importconfigs);

        return $importconfigs;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM importconfigs ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
