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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing $model
 */
class Dao extends Model\DataObject\ClassDefinition\CustomLayout\Dao
{
    /**
     * Loads a list of custom layouts for the specified parameters, returns an array of DataObject\ClassDefinition\CustomLayout elements
     *
     */
    public function load(): array
    {
        $layouts = [];

        foreach ($this->loadIdList() as $id) {
            $customLayout = Model\DataObject\ClassDefinition\CustomLayout::getById($id);
            if ($customLayout) {
                $layouts[] = $customLayout;
            }
        }
        if ($this->model->getFilter()) {
            $layouts = array_filter($layouts, $this->model->getFilter());
        }
        if (is_callable($this->model->getOrder())) {
            usort($layouts, $this->model->getOrder());
        }
        $this->model->setLayoutDefinitions($layouts);

        return $layouts;
    }

    public function getTotalCount(): int
    {
        try {
            $layouts = [];
            foreach ($this->loadIdList() as $id) {
                $customLayout = Model\DataObject\ClassDefinition\CustomLayout::getById($id);
                if ($customLayout) {
                    $layouts[] = $customLayout;
                }
            }

            if ($this->model->getFilter()) {
                $layouts = array_filter($layouts, $this->model->getFilter());
            }

            return count($layouts);
        } catch (Exception $e) {
            return 0;
        }
    }
}
