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

class User_Permission {

    /**
     * @var string
     */
    public $name;

    /**
     * @var boolean
     */
    public $inherited;

    /**
     *
     * @param String $name
     * @param boolean $inherited
     * @return User_Permission $userPermission returns new User_Permission object
     */
    public function __construct($name, $inherited) {
        $this->name = $name;
        $this->inherited = $inherited;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function getInherited() {
        return $this->inherited;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param boolean $inherited
     * @return void
     */
    public function setInherited($inherited) {
        $this->inherited = (bool) $inherited;
    }
}
