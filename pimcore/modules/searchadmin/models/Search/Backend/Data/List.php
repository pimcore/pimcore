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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
class Search_Backend_Data_List extends Pimcore_Model_List_Abstract {

    /**
     * @var Search_Backend_Data[]
     */
    public $entries;

    /**
     * @var array
     */
    public $validOrderKeys = array(
        "id",
        "fullpath",
        "maintype",
        "type",
        "subtype",
        "published",
        "creationdate",
        "modificationdate",
        "userowner",
        "userModification"
    );

    /**
     * @return Search_Backend_Data[]
     */
    public function getEntries(){
        return $this->entries;
    }

    /**
     * @param  Search_Backend_Data[] $entries
     * @return void
     */
    public function setEntries($entries){
        $this->entries = $entries;
    }

    /**
     * @param boolean $objectTypeObject
     * @return void
     */
    public function __construct() {
        $this->initResource("Search_Backend_Data_List");

    }

    /**
	 * @param string $key
	 * @return boolean
	 */
	public function isValidOrderKey ($key) {
		if(in_array($key,$this->validOrderKeys)) {
			return true;
		}
		return false;
	}



}