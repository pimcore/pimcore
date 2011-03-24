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

class Asset_Image_Thumbnail_List_Resource_Mysql extends Pimcore_Model_List_Resource_Mysql_Abstract {

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Thumbnail elements
     *
     * @return array
     */
    public function load() {

        $thumbnails = array();
        $thumbnailsData = $this->db->fetchAll("SELECT id FROM thumbnails" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($thumbnailsData as $thumbnailData) {
            try {
                $thumbnails[] = Asset_Image_Thumbnail::getById($thumbnailData["id"]);
            } catch (Exception $e) {
                Logger::error($e);
            }
        }

        $this->model->setThumbnails($thumbnails);
        return $thumbnails;
    }

}
