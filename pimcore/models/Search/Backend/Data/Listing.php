<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        $this->initDao("\\Pimcore\\Model\\Search\\Backend\\Data\\Listing");

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
