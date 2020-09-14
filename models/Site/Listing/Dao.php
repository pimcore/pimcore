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
 * @package    Site
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Site\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Site\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Thumbnail elements
     *
     * @return array
     */
    public function load()
    {
        $sites = [];
        $sitesData = $this->db->fetchCol('SELECT id FROM sites' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($sitesData as $siteData) {
            $sites[] = Model\Site::getById($siteData);
        }

        $this->model->setSites($sites);

        return $sites;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM sites ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
