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

namespace Pimcore\Model\User\Listing;

use Pimcore\Model;

class AbstractListing extends Model\Listing\AbstractListing {


    /**
     * @var string
     */
    public $type;

    /**
     * Contains the results of the list. They are all an instance of User
     *
     * @var array
     */
    public $items = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     * @todo remove the dummy-always-true rule
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @param array $items
     * @return void
     */
    public function setItems($items) {
        $this->items = $items;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

}
