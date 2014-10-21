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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Search\Backend\Data;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
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
        "creationDate",
        "modificationDate",
        "userOwner",
        "userModification"
    );

    /**
     * @return array
     */
    public function getEntries(){
        return $this->entries;
    }

    /**
     * @param $entries
     * @return $this
     */
    public function setEntries($entries){
        $this->entries = $entries;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function __construct() {
        $this->initResource("\\Pimcore\\Model\\Search\\Backend\\Data\\Listing");

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
