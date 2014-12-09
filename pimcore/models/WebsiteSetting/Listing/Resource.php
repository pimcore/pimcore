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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\WebsiteSetting\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of static routes for the specifies parameters, returns an array of Staticroute elements
     *
     * @return array
     */
    public function load() {

        $sql = "SELECT id FROM website_settings" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $settingsData = $this->db->fetchCol($sql, $this->model->getConditionVariables());

        $settings = array();
        foreach ($settingsData as $settingData) {
            $settings[] = Model\WebsiteSetting::getById($settingData);
        }

        $this->model->setSettings($settings);
        return $settings;
    }


    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM website_settings " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

}
