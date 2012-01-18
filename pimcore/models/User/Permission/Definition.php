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

class User_Permission_Definition extends Pimcore_Model_Abstract {

    public $key;

    /**
     * @param array
     */
    public function __construct($data = array()) {
        if (is_array($data) && !empty($data)) {
            $this->setValues($data);
        }
    }

    /**
     * @return string
     */
    function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    function setKey($key) {
        $this->key = $key;
    }
}
