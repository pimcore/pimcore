<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Listing;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\Listing\AbstractListing\Dao getDao()
 */
class AbstractListing extends Model\Listing\AbstractListing
{


    /**
     * @var string
     */
    public $type;

    /**
     * Contains the results of the list. They are all an instance of User
     *
     * @var array
     */
    public $items = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     * @todo remove the dummy-always-true rule
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function setItems($items)
    {
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
