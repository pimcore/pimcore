<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
