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

namespace Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute\Listing;

use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;

/**
 * @internal
 *
 * @property Staticroute\Listing $model
 */
class Dao extends Staticroute\Dao
{
    public function loadList(): array
    {
        $staticRoutes = [];
        foreach ($this->loadIdList() as $id) {
            $staticRoutes[] = Staticroute::getById($id);
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

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
