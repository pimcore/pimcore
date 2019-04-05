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
 */
class Listing extends AbstractListing
{
    /**
     * @var array
     */
    protected $notifications;

    /**
     * @var array
     */
    protected $data;

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
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getData(): ?array
    {
        if (null === $this->data) {
            $this->setData($this->getDao()->load());
        }

        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return Listing
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->getDao()->count();
    }

    /**
     * @return Model\Notification[]
     */
    public function load(): array
    {
        return $this->getDao()->load();
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

        return $this->getDao()->load();
    }

    /**
     * @return Model\Notification[]
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @param array $notifications
     *
     * @return Listing
     */
    public function setNotifications(array $notifications): self
    {
        $this->notifications = $notifications;

        return $this;
    }
}
