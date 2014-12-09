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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Object|Class
     *
     * @var array
     */
    public $classes;


    /**
     * @param $key
     * @return bool
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    function getClasses() {
        return $this->classes;
    }

    /**
     * @param $classes
     * @return $this
     */
    function setClasses($classes) {
        $this->classes = $classes;
        return $this;
    }
}
