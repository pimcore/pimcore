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
 * @package    Redirect
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Redirect\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Redirect\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Redirect elements
     *
     * @return array
     */
    public function load()
    {
        $redirectsData = $this->db->fetchCol("SELECT id FROM redirects" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $redirects = [];
        foreach ($redirectsData as $redirectData) {
            $redirects[] = Model\Redirect::getById($redirectData);
        }

        $this->model->setRedirects($redirects);

        return $redirects;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM redirects " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
