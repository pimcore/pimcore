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

namespace Pimcore\Model\ImportConfigShare\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\ImportConfigShare\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of import config shares for the specified parameters, returns an array of ImportConfigShare elements
     *
     * @return array
     */
    public function load()
    {
        $shares = [];
        $data = $this->db->fetchAll('SELECT * FROM importconfig_shares' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $configData) {
            $share = new Model\ImportConfig();
            $share->setValues($configData);
            $shares[] = $share;
        }

        $this->model->setImportConfigShares($shares);

        return $shares;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM importconfig_shares ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
