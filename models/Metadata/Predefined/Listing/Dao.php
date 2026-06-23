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

namespace Pimcore\Model\Metadata\Predefined\Listing;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Metadata\Predefined\Listing $model
 */
class Dao extends Model\Metadata\Predefined\Dao
{
    /**
     * Loads a list of predefined metadata definitions for the specicified parameters, returns an array of
     * Metadata\Predefined elements
     *
     * @return \Pimcore\Model\Metadata\Predefined[]
     */
    public function loadList(): array
    {
        $properties = [];
        foreach ($this->loadIdList() as $id) {
            $properties[] = Model\Metadata\Predefined::getById($id);
        }
        if ($this->model->getFilter()) {
            $properties = array_filter($properties, $this->model->getFilter());
        }
        if ($this->model->getOrder()) {
            usort($properties, $this->model->getOrder());
        }

        $this->model->setDefinitions($properties);

        return $properties;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
