<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\IndexService\Config;

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
     * @param $tenantConfigXml
     * @param null $totalConfigXml
     */
    public function __construct($tenantName, $tenantConfigXml, $totalConfigXml = null) {
        $this->tenantName = $tenantName;
        $this->attributeConfig = $tenantConfigXml->columns;
        $this->filterTypeConfig = $tenantConfigXml->filtertypes;

        $this->searchAttributeConfig = array();
        if($tenantConfigXml->generalSearchColumns->column) {
            foreach($tenantConfigXml->generalSearchColumns->column as $c) {
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

}