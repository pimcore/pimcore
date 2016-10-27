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
 * @package    Notification
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Notification;

use Pimcore\Model;
use Pimcore\Model\Notification;

/**
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 *
 * @method Listing\Dao getDao()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int loadIdList()
 */
class Listing extends Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator
{
    /**
     * Contains the results of the list
     *
     * @var Notification[]|null
     */
    public $notifications = null;

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @return Notification[]
     */
    public function getNotifications()
    {
        if ($this->notifications === null) {
            $this->load();
        }

        return $this->notifications;
    }

    /**
     * @param array $notifications
     *
     * @return $this
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;

        return $this;
    }

    /**
     * Returns the total items count.
     *
     * @return int
     */
    public function count()
    {
        return $this->getTotalCount();
    }

    /**
     * Returns the listing based on defined offset and limit as parameters.
     *
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return Listing
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * @return $this
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }

    /**
     * Rewind the listing back to te start.
     *
     * @return void
     */
    public function rewind()
    {
        $this->getNotifications();
        reset($this->notifications);
    }

    /**
     * Returns the current listing row.
     *
     * @return Notification
     */
    public function current()
    {
        $this->getNotifications();
        $var = current($this->notifications);

        return $var;
    }

    /**
     * Returns the current listing row key.
     *
     * @return Notification|null
     */
    public function key()
    {
        $this->getNotifications();
        $var = key($this->notifications);

        return $var;
    }

    /**
     * Returns the next listing row key.
     *
     * @return Notification|false
     */
    public function next()
    {
        $this->getNotifications();
        $var = next($this->notifications);

        return $var;
    }

    /**
     * Checks whether the listing contains more entries.
     *
     * @return bool
     */
    public function valid()
    {
        $this->getNotifications();
        $var = $this->current() !== false;

        return $var;
    }
}
