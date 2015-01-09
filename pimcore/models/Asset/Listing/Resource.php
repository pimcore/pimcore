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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {


    /**
     * Get the assets from database
     *
     * @return array
     */
    public function load() {

        $assets = array();
        $assetsData = $this->db->fetchAll("SELECT id,type FROM assets" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($assetsData as $assetData) {
            if($assetData["type"]) {
                if($asset = Model\Asset::getById($assetData["id"])) {
                    $assets[] = $asset;
                }
            }
        }

        $this->model->setAssets($assets);
        return $assets;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        $assetIds = $this->db->fetchCol("SELECT id FROM assets" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $assetIds;
    }

    public function getCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM assets" . $this->getCondition() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount;
    }

    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM assets" . $this->getCondition(), $this->model->getConditionVariables());
        return $amount;
    }
}