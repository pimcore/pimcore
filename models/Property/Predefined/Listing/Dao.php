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

namespace Pimcore\Model\Property\Predefined\Listing;

use Pimcore\Model;
use Pimcore\Model\Property;

/**
 * @internal
 *
 * @property \Pimcore\Model\Property\Predefined\Listing $model
 */
class Dao extends Model\Property\Predefined\Dao
{
    /**
     * Loads a list of predefined properties for the specicifies parameters, returns an array of Property\Predefined elements
     *
     * @return Model\Property\Predefined[]
     */
    public function loadList(): array
    {
        $properties = [];

        foreach ($this->loadIdList() as $id) {
            $properties[] = Model\Property\Predefined::getById($id);
        }
        if ($this->model->getFilter()) {
            $properties = array_filter($properties, $this->model->getFilter());
        }
        if ($this->model->getOrder()) {
            usort($properties, $this->model->getOrder());
        }

        $this->model->setProperties($properties);

        return $properties;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
