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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Targeting_Rule_List extends Pimcore_Model_List_Abstract {

    /**
     * Contains the results of the list. They are all an instance of Tool_Targeting_Rule
     *
     * @var array
     */
    public $targets = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @param array $targets
     */
    public function setTargets($targets)
    {
        $this->targets = $targets;
        return $this;
    }

    /**
     * @return array
     */
    public function getTargets()
    {
        return $this->targets;
    }

}
