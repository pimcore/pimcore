<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\Fieldcollection;

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
     * @return float|null
     */
    abstract public function getPageLimit(): ?float;

    /**
     * returns list of available fields for sorting ascending
     *
     * @abstract
     *
     * @return string|null
     */
    abstract public function getOrderByAsc(): ?string;

    /**
     * returns list of available fields for sorting descending
     *
     * @abstract
     *
     * @return string|null
     */
    abstract public function getOrderByDesc(): ?string;

    /**
     * return array of field collections for preconditions
     *
     * @abstract
     *
     * @return Fieldcollection<AbstractFilterDefinitionType>|null
     */
    abstract public function getConditions(): ?Fieldcollection;

    /**
     * return array of field collections for filters
     *
     * @abstract
     *
     * @return Fieldcollection<AbstractFilterDefinitionType>|null
     */
    abstract public function getFilters(): ?Fieldcollection;

    /**
     * enables inheritance for field collections, if xxxInheritance field is available and set to string 'true'
     *
     * @param string $key
     *
     * @return Fieldcollection|null
     */
    public function preGetValue(string $key): ?Fieldcollection
    {
        if ($this->getClass()->getAllowInherit()
            && DataObject::doGetInheritedValues()
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
                }
                if (!empty($parentValue)) {
                    $value = new Fieldcollection($parentValue->getItems());
                    foreach ($data as $entry) {
                        $value->add($entry);
                    }
                } else {
                    $value = new Fieldcollection($data->getItems());
                }

                return $value;
            }
        }

        return null;
    }
}
