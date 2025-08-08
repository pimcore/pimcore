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

namespace Pimcore\Model\Asset\Image\Thumbnail\Config\Listing;

use Pimcore\Model\Asset\Image\Thumbnail\Config;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Image\Thumbnail\Config\Listing $model
 */
class Dao extends Config\Dao
{
    public function loadList(): array
    {
        $configs = [];

        foreach ($this->loadIdList() as $name) {
            $configs[] = Config::getByName($name);
        }
        if ($this->model->getFilter()) {
            $configs = array_filter($configs, $this->model->getFilter());
        }
        if ($this->model->getOrder()) {
            usort($configs, $this->model->getOrder());
        }

        $this->model->setThumbnails($configs);

        return $configs;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
