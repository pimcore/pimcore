<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
        $settingsData = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $settings = [];
        foreach ($settingsData as $settingData) {
            $settings[] = Model\WebsiteSetting::getById($settingData);
        }

        $this->model->setSettings($settings);

        return $settings;
    }

    public function getTotalCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM website_settings ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }
}
