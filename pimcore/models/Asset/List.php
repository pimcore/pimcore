<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_List extends Pimcore_Model_List_Abstract implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    /**
     * List of assets
     *
     * @var array
     */
    public $assets = null;

    /**
     * List of valid order keys
     *
     * @var array
     */
    public $validOrderKeys = array(
        "creationDate",
        "modificationDate",
        "id",
        "filename",
        "type",
        "parentId",
        "path",
        "mimetype"
    );

    /**
     * Test if the passed key is valid
     *
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;

        /*if (in_array($key, $this->validOrderKeys)) {
            return true;
        }
        return false;*/
    }

    /**
     * @return array
     */
    public function getAssets() {
        if ($this->assets === null) {
            $this->load();
        }
        return $this->assets;
    }

    /**
     * @param string $assets
     * @return void
     */
    public function setAssets($assets) {
        $this->assets = $assets;
    }
    
    
    /**
     *
     * Methods for Zend_Paginator_Adapter_Interface
     */

    public function count() {
        return $this->getTotalCount();
    }

    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
        return $this->load();
    }

    public function getPaginatorAdapter() {
        return $this;
    }
    

    /**
     * Methods for Iterator
     */

    public function rewind() {
        $this->getAssets();
        reset($this->assets);
    }

    public function current() {
        $this->getAssets();
        $var = current($this->assets);
        return $var;
    }

    public function key() {
        $this->getAssets();
        $var = key($this->assets);
        return $var;
    }

    public function next() {
        $this->getAssets();
        $var = next($this->assets);
        return $var;
    }

    public function valid() {
        $this->getAssets();
        $var = $this->current() !== false;
        return $var;
    }
}
