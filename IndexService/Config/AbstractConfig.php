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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

abstract class AbstractConfig implements IConfig {

    protected $tenantName;
    protected $attributeConfig;
    protected $searchAttributeConfig;

    /**
     * @var \Zend_Config
     */
    protected $filterTypeConfig;

    /**
     * @param string $tenantName
     * @param $tenantConfig
     * @param null $totalConfig
     */
    public function __construct($tenantName, $tenantConfig, $totalConfig = null) {
        $this->tenantName = $tenantName;
        $attributeConfigArray = [];

        /* include column file configs and replace placeholders */
        foreach ($tenantConfig->columns->toArray() as $columnConfig) {
            if (!array_key_exists("file", $columnConfig)) {
                $attributeConfigArray[] = $columnConfig;
                continue;
            }

            $includeColumnConfig = include PIMCORE_DOCUMENT_ROOT . (string)$columnConfig['file'];

            /* if placeholders are defined, check for them in the included config */
            if (array_key_exists("placeholders", $columnConfig)) {
                $placeholders = $columnConfig['placeholders'];
                foreach ($includeColumnConfig as $incIndex => $replaceConfig) {
                    foreach ($replaceConfig as $key => $value) {
                        if (array_key_exists($value, $placeholders)) {
                            $includeColumnConfig[$incIndex][$key] = $placeholders[$value];
                        }
                    }
                }
            }

            $attributeConfigArray = array_merge($attributeConfigArray, $includeColumnConfig);
        }

        $this->attributeConfig = new \Zend_Config($attributeConfigArray);

        $this->filterTypeConfig = $tenantConfig->filtertypes;

        if(sizeof($tenantConfig->generalSearchColumns) == 1) {
            $this->searchAttributeConfig[] = (string)$tenantConfig->generalSearchColumns->name;
        } elseif($tenantConfig->generalSearchColumns) {
            foreach($tenantConfig->generalSearchColumns as $c) {
                $this->searchAttributeConfig[] = $c->name;
            }
        }
    }

    /**
     * @return string
     */
    public function getTenantName() {
        return $this->tenantName;
    }

    /**
     * returns column configuration for product index
     *
     * @return mixed
     */
    public function getAttributeConfig() {
        return $this->attributeConfig;
    }

    /**
     * return search index column names for product index
     *
     * @return array
     */
    public function getSearchAttributeConfig() {
        return $this->searchAttributeConfig;
    }

    /**
     * return all supported filter types for product index
     *
     * @return array|null
     */
    public function getFilterTypeConfig()
    {
        return $this->filterTypeConfig;
    }


    /**
     * @return bool
     */
    public function isActive(\OnlineShop\Framework\Model\IIndexable $object) {
        return true;
    }

    /**
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return \OnlineShop\Framework\Model\AbstractCategory[]
     */
    public function getCategories(\OnlineShop\Framework\Model\IIndexable $object)
    {
        return $object->getCategories();
    }

    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return \OnlineShop\Framework\Model\IIndexable[]
     */
    public function createSubIdsForObject(\OnlineShop\Framework\Model\IIndexable $object) {
        return array($object->getId() => $object);
    }

    /**
     * checks if there are some zombie subIds around and returns them for cleanup
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param array $subIds
     * @return mixed
     */
    public function getSubIdsToCleanup(\OnlineShop\Framework\Model\IIndexable $object, array $subIds) {
        return array();
    }

    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param $subId
     * @return mixed
     */
    public function createVirtualParentIdForSubId(\OnlineShop\Framework\Model\IIndexable $object, $subId) {
        return $object->getOSParentId();
    }

    /**
     * Gets object by id, can consider subIds and therefore return e.g. an array of values
     * always returns object itself - see also getObjectMockupById
     *
     * @param $objectId
     * @param $onlyMainObject - only returns main object
     * @return mixed
     */
    public function getObjectById($objectId, $onlyMainObject = false) {
        return \Pimcore\Model\Object\AbstractObject::getById($objectId);
    }



    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        return $this->getObjectById($objectId);
    }


    /**
     * returns column type for id
     *
     * @param $isPrimary
     * @return string
     */
    public function getIdColumnType($isPrimary)
    {
        if($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return "int(11) NOT NULL";
        }
    }
}