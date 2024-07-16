<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Site\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Site\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Thumbnail elements
     *
     */
    public function load(): array
    {
        $sites = [];
        $sitesData = $this->db->fetchFirstColumn('SELECT id FROM sites' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($sitesData as $siteData) {
            $sites[] = Model\Site::getById($siteData);
        }

        $this->model->setSites($sites);

        return $sites;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM sites ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
