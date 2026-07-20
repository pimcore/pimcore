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

namespace Pimcore\Model\Asset\Video\Thumbnail\Config\Listing;

use Pimcore\Model\Asset\Video\Thumbnail\Config;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Video\Thumbnail\Config\Listing $model
 */
class Dao extends Config\Dao
{
    public function loadList(): array
    {
        $configs = [];

        foreach ($this->loadIdList() as $name) {
            $configs[] = Config::getByName($name);
        }

        $this->model->setThumbnails($configs);

        return $configs;
    }

    public function getTotalCount(): int
    {
        return count($this->loadIdList());
    }
}
