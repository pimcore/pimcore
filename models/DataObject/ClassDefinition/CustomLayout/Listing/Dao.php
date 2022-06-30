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
     * @return array
     */
    public function load()
    {
        $layouts = [];

        foreach ($this->loadIdList() as $id) {
            $customLayout = Model\DataObject\ClassDefinition\CustomLayout::getById($id);
            if ($customLayout) {
                $layouts[] = $customLayout;
            }
        }

        $this->model->setLayoutDefinitions($layouts);

        return $layouts;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return count($this->loadIdList());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @TODO remove in Pimcore 11
     *
     * @return array
     */
    protected function loadIdList(): array
    {
        $list = parent::loadIdList();

        return array_merge($list, array_keys($this->loadLegacyConfigs()));
    }

    /**
     * @TODO remove in Pimcore 11
     *
     * @return array
     */
    private function loadLegacyConfigs(): array
    {
        $files = glob(PIMCORE_CUSTOMLAYOUT_DIRECTORY . '/*.php');

        $layouts = [];
        foreach ($files as $file) {
            $layout = @include $file;
            if ($layout instanceof Model\DataObject\ClassDefinition\CustomLayout) {
                $layouts[$layout->getId()] = $layout->getObjectVars();
            }
        }

        return $layouts;
    }
}
