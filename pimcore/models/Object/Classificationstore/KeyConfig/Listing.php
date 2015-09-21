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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Classificationstore\KeyConfig;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Classificationstore_KeyConfig
     *
     * @var array
     */
    public $list = array();

    /** @var  bool */
    public $includeDisabled;

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getList() {
        return $this->list;
    }

    /**
     * @param array
     * @return void
     */
    public function setList($theList) {
        $this->list = $theList;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIncludeDisabled()
    {
        return $this->includeDisabled;
    }

    /**
     * @param boolean $includeDisabled
     */
    public function setIncludeDisabled($includeDisabled)
    {
        $this->includeDisabled = $includeDisabled;
    }


}
