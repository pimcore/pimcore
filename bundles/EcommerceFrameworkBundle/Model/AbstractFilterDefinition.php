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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;

/**
 * Abstract base class for filter definition pimcore objects
 */
abstract class AbstractFilterDefinition extends DataObject\Concrete implements DataObject\PreGetValueHookInterface
{
    /**
     * returns page limit for product list
     *
     * @abstract
     *
     * @return float
     */
    abstract public function getPageLimit();

    /**
     * returns list of available fields for sorting ascending
     *
     * @abstract
     *
     * @return string
     */
    abstract public function getOrderByAsc();

    /**
     * returns list of available fields for sorting descending
     *
     * @abstract
     *
     * @return string
     */
    abstract public function getOrderByDesc();

    /**
     * return array of field collections for preconditions
     *
     * @abstract
     *
     * @return \Pimcore\Model\DataObject\Fieldcollection
     */
    abstract public function getConditions();

    /**
     * return array of field collections for filters
     *
     * @abstract
     *
     * @return \Pimcore\Model\DataObject\Fieldcollection
     */
    abstract public function getFilters();

    /**
     * enables inheritance for field collections, if xxxInheritance field is available and set to string 'true'
     *
     * @param string $key
     *
     * @return mixed|\Pimcore\Model\DataObject\Fieldcollection
     */
    public function preGetValue(string $key)
    {
        if ($this->getClass()->getAllowInherit()
            && DataObject\AbstractObject::doGetInheritedValues()
            && $this->getClass()->getFieldDefinition($key) instanceof DataObject\ClassDefinition\Data\Fieldcollections
        ) {
            $checkInheritanceKey = $key . 'Inheritance';
            if ($this->{
                'get' . $checkInheritanceKey
                }() == 'true'
            ) {
                try {
                    $parentValue = $this->getValueFromParent($key);
                } catch (InheritanceParentNotFoundException $e) {
                    $parentValue = null;
                }

                $data = $this->$key;
                if (!$data) {
                    $data = $this->getClass()->getFieldDefinition($key)->preGetData($this);
                }
                if (!$data) {
                    return $parentValue;
                } else {
                    if (!empty($parentValue)) {
                        $value = new DataObject\Fieldcollection($parentValue->getItems());
                        if (!empty($data)) {
                            foreach ($data as $entry) {
                                $value->add($entry);
                            }
                        }
                    } else {
                        $value = new DataObject\Fieldcollection($data->getItems());
                    }

                    return $value;
                }
            }
        }

        return null;
    }
}
