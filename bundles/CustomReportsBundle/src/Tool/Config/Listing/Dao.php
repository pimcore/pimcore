<?php

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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing $model
 */
class Dao extends \Pimcore\Bundle\CustomReportsBundle\Tool\Config\Dao
{
    /**
     * @return Config[]
     */
    public function loadList(): array
    {
        $configs = [];

        $idList = $this->loadIdList();
        foreach ($idList as $name) {
            $configs[] = Config::getByName($name);
        }

        return $configs;
    }

    /**
     *
     * @return Config[]
     */
    public function loadForGivenUser(Model\User $user): array
    {
        $allConfigs = $this->loadList();

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

    public function getTotalCount(): int
    {
        return count($this->loadIdList());
    }
}
