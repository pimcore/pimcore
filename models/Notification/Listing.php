<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Notification;

use Pimcore\Model;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @method Listing\Dao getDao()
 * @method Model\Notification[] load()
 */
class Listing extends AbstractListing
{
    public function isValidOrderKey(string $key): bool
    {
        return true;
    }

    /**
     *
     * @return Model\Notification[]
     */
    public function getItems(int $offset, ?int $limit): array
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
     * @param Model\Notification[]|null $notifications
     *
     * @return $this
     */
    public function setNotifications(?array $notifications): static
    {
        return $this->setData($notifications);
    }
}
