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
