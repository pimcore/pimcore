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
 * @category   Pimcore
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Config\Listing;

use Pimcore\Model;
use Pimcore\Model\Tool\CustomReport\Config;

/**
 * @property \Pimcore\Model\Tool\CustomReport\Config\Listing $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('custom-reports');
    }

    /**
     * @return Config[]
     */
    public function load()
    {
        $properties = [];
        $propertiesData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        foreach ($propertiesData as $propertyData) {
            $properties[] = Config::getByName($propertyData['id']);
        }

        $this->model->setReports($properties);

        return $properties;
    }

    /**
     * @param Model\User $user
     *
     * @return Config[]
     */
    public function loadForGivenUser(Model\User $user)
    {
        $allConfigs = $this->load();

        if ($user->isAdmin()) {
            return $allConfigs;
        }

        $filteredConfigs = [];
        foreach ($allConfigs as $config) {
            if ($config->getShareGlobally()) {
                $filteredConfigs[] = $config;
            } elseif ($config->getSharedUserIds() && in_array($user->getId(), $config->getSharedUserIds())) {
                $filteredConfigs[] = $config;
            } elseif ($config->getSharedRoleIds() && array_intersect($user->getRoles(), $config->getSharedRoleIds())) {
                $filteredConfigs[] = $config;
            }
        }

        return $filteredConfigs;
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
