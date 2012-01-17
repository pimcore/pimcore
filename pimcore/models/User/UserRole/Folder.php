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
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_UserRole_Folder extends User_Abstract {

    /**
     * @var boolean
     */
    public $hasChilds;

    /**
     * @param boolean $state
     */
    function setHasChilds($state){
        $this->hasChilds= $state;

    }

    /**
     * Returns true if the document has at least one child
     *
     * @return boolean
     */
    public function hasChilds() {
        if ($this->hasChilds !== null) {
            return $this->hasChilds;
        }
        return $this->getResource()->hasChilds();
    }
}
