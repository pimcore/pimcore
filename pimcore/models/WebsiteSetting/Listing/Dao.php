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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\WebsiteSetting\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\WebsiteSetting\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of static routes for the specified parameters, returns an array of Staticroute elements
     *
     * @return array
     */
    public function load()
    {
        $sql = "SELECT id FROM website_settings" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $settingsData = $this->db->fetchCol($sql, $this->model->getConditionVariables());

        $settings = [];
        foreach ($settingsData as $settingData) {
            $settings[] = Model\WebsiteSetting::getById($settingData);
        }

        $this->model->setSettings($settings);

        return $settings;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $amount = 0;

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM website_settings " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
