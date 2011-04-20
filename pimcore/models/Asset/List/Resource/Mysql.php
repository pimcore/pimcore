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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_List_Resource_Mysql extends Pimcore_Model_List_Resource_Mysql_Abstract {


    /**
     * Get the assets from database
     *
     * @return array
     */
    public function load() {

        $assets = array();
        $assetsData = $this->db->fetchAll("SELECT id,type FROM assets" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($assetsData as $assetData) {
            if($assetData["type"]) {
                $assets[] = Asset::getById($assetData["id"]);
            }
        }

        $this->model->setAssets($assets);
        return $assets;
    }
    
    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM assets" . $this->getCondition());

        return $amount["amount"];
    }
}