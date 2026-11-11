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

namespace Pimcore\Model\User\ServiceUser;

use Pimcore\Model\User;
use Pimcore\Model\User\Listing\AbstractListing;
use Pimcore\Model\User\Listing\AbstractListing\Dao;

/**
 * Lists service users (\Pimcore\Model\User objects whose isServiceUser() is true). Service users are a flat list
 * without folders, and \Pimcore\Model\User\Listing never contains them.
 *
 * @method Dao getDao()
 * @method User[] load()
 * @method User|false current()
 */
class Listing extends AbstractListing
{
    protected string $type = 'serviceuser';

    /**
     * Alias for $this->getItems()
     *
     * @return User[]
     */
    public function getServiceUsers(): array
    {
        return $this->getItems();
    }
}
