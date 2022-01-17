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

namespace Pimcore\Model\Staticroute\Listing;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Staticroute\Listing $model
 */
class Dao extends Model\Staticroute\Dao
{
    /**
     * @return array
     */
    public function loadList()
    {
        $staticRoutes = [];
        foreach ($this->loadIdList() as $id) {
            $staticRoutes[] = Model\Staticroute::getById($id);
        }

        if ($this->model->getFilter()) {
            $staticRoutes = array_filter($staticRoutes, $this->model->getFilter());
        }
        if ($this->model->getOrder()) {
            usort($staticRoutes, $this->model->getOrder());
        }
        $this->model->setRoutes($staticRoutes);

        return $staticRoutes;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return count($this->loadList());
    }
}
