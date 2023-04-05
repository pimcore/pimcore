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

namespace Pimcore\Bundle\AdminBundle\Model\GridConfigFavourite\Listing;

use Pimcore\Bundle\AdminBundle\Model\GridConfig;
use Pimcore\Bundle\AdminBundle\Model\GridConfigFavourite;
use Pimcore\Model;

/**
 * @internal
 *
 * @property GridConfigFavourite\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of gridconfigs for the specicified parameters, returns an array of GridConfigFavourite elements
     *
     * @return array
     */
    public function load(): array
    {
        $gridConfigsFavourites = [];
        $data = $this->db->fetchAllAssociative('SELECT * FROM gridconfig_favourites' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        $gridConfigs = [];

        foreach ($data as $configData) {
            $gridConfig = new GridConfig();
            $gridConfig->setValues($configData);
            $gridConfigs[] = $gridConfig;
        }

        $this->model->setGridconfigFavourites($gridConfigsFavourites);

        return $gridConfigs;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM gridconfig_favourites ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
