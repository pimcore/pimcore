<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Site
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Site\Listing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Thumbnail elements
     *
     * @return array
     */
    public function load() {

        $sites = array();
        $sitesData = $this->db->fetchCol("SELECT id FROM sites" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($sitesData as $siteData) {
            $sites[] = Model\Site::getById($siteData);
        }

        $this->model->setSites($sites);
        return $sites;
    }

}
