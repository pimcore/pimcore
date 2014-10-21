<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Site
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Site\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

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
