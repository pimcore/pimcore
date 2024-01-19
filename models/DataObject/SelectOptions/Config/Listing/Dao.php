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

namespace Pimcore\Model\DataObject\SelectOptions\Config\Listing;

use Pimcore\Model\DataObject\SelectOptions\Config;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\SelectOptions\Config\Listing $model
 */
class Dao extends Config\Dao
{
    public function loadList(): array
    {
        $configs = [];
        foreach ($this->loadIdListByReadTargets() as $id) {
            $configs[] = Config::getById($id);
        }

        $this->model->setSelectOptions($configs);

        return $configs;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
