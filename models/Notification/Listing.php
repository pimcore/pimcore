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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

declare(strict_types=1);

namespace Pimcore\Model\Notification;

use Pimcore\Model;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @method Listing\Dao getDao()
 * @method Model\Notification[] load()
 * @method int count()
 */
class Listing extends AbstractListing
{
    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $notifications = null;

    public function __construct()
    {
        $this->notifications = & $this->data;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Model\Notification[]
     */
    public function getItems(int $offset, int $limit): array
    {
        $this->setOffset($offset);
        $this->setLimit($limit);

        return $this->getData();
    }

    /**
     * @return Model\Notification[]
     */
    public function getNotifications(): array
    {
        return $this->getData();
    }

    /**
     * @param array $notifications
     *
     * @return Listing
     */
    public function setNotifications(array $notifications): self
    {
        return $this->setData($notifications);
    }
}
