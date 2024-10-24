<?php
declare(strict_types=1);

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

namespace Pimcore\Model\WebsiteSetting\Listing;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\WebsiteSetting\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @return \Pimcore\Model\WebsiteSetting[]
     */
    public function load(): array
    {
        $sql = 'SELECT id FROM website_settings' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $settingsData = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables());

        $settings = [];
        foreach ($settingsData as $settingData) {
            $settings[] = Model\WebsiteSetting::getById($settingData);
        }

        $this->model->setSettings($settings);

        return $settings;
    }

    public function getTotalCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM website_settings ' . $this->getCondition(), $this->model->getConditionVariables());
    }
}
