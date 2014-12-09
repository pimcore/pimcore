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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Targeting\Persona;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Tool\Targeting\Persona
     *
     * @var array
     */
    public $personas = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @param $personas
     * @return $this
     */
    public function setPersonas($personas)
    {
        $this->personas = $personas;
        return $this;
    }

    /**
     * @return array
     */
    public function getPersonas()
    {
        return $this->personas;
    }

}
