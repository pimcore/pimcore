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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\WebsiteSetting\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\WebsiteSetting\Listing $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('website-settings');
    }

    /**
     * Loads a list of static routes for the specified parameters, returns an array of Staticroute elements
     *
     * @return \Pimcore\Model\WebsiteSetting[]
     */
    public function load()
    {
        $settingsData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        $settings = [];
        foreach ($settingsData as $settingData) {
            $settings[] = Model\WebsiteSetting::getById($settingData['id']);
        }

        $this->model->setSettings($settings);

        return $settings;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $data = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());
        $amount = count($data);

        return $amount;
    }
}
