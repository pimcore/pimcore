<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Redirect
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Redirect\Listing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Redirect elements
     *
     * @return array
     */
    public function load() {

        $redirectsData = $this->db->fetchCol("SELECT id FROM redirects" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $redirects = array();
        foreach ($redirectsData as $redirectData) {
            $redirects[] = Model\Redirect::getById($redirectData);
        }

        $this->model->setRedirects($redirects);
        return $redirects;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM redirects " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

}
