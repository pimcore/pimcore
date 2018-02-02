<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Model\DataObject\Concrete;

abstract class AbstractObjectListCondition
{
    /**
     * Handle serializing of object list to list of IDs
     *
     * @param $objectProperty
     * @param $idProperty
     *
     * @return array
     */
    protected function handleSleep($objectProperty, $idProperty)
    {
        $itemIds = [];

        /** @var Concrete $item */
        foreach ($this->$objectProperty as $key => $item) {
            $itemIds[$key] = $item->getId();
        }

        $this->$idProperty = $itemIds;

        return [$idProperty];
    }

    /**
     * Handle loading of object list from serialized list of IDs
     *
     * @param $objectProperty
     * @param $idProperty
     */
    protected function handleWakeup($objectProperty, $idProperty)
    {
        // support for legacy version with IDs serialized directly to property
        $source = (null !== $this->$objectProperty && count($this->$objectProperty) > 0)
            ? $this->$objectProperty
            : $this->$idProperty;

        $items = [];
        if (null !== $source && is_array($source)) {
            foreach ($source as $key => $sourceId) {
                $item = $this->loadObject($sourceId);

                if ($item) {
                    $items[$key] = $item;
                }
            }
        }

        $this->$objectProperty = $items;
    }

    /**
     * Load object by ID
     *
     * @param int $id
     *
     * @return Concrete|null
     */
    protected function loadObject($id)
    {
        return Concrete::getById($id);
    }
}
