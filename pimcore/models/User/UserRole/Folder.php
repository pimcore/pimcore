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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

class Folder extends Model\User\AbstractUser {

    /**
     * @var boolean
     */
    public $hasChilds;

    /**
     * @param $state
     * @return $this
     */
    function setHasChilds($state){
        $this->hasChilds= $state;
        return $this;
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
