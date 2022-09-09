<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Model\Paginator\PaginateListingInterface;

/**
 * @method Model\Asset[] load()
 * @method Model\Asset|false current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \Pimcore\Model\Asset\Listing\Dao getDao()
 * @method onCreateQueryBuilder(?callable $callback)
 */
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * @return Model\Asset[]
     */
    public function getAssets()
    {
        return $this->getData();
    }

    /**
     * @param Model\Asset[] $assets
     *
     * @return $this
     */
    public function setAssets($assets)
    {
        return $this->setData($assets);
    }

    /**
     *
     * Methods for AdapterInterface
     */

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()// : int
    {
        return $this->getTotalCount();
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return Model\Asset[]
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * @internal
     *
     * @param Model\User $user
     * @param Model\Asset $asset

     *
     * @return $this
     */
    public function filterAccessibleByUser(Model\User $user, Model\Asset $asset)
    {
        if (!$user->isAdmin()) {
            $userIds = $user->getRoles();
            $currentUserId = $user->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $asset->getDao()->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_asset uwa WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(path,filename),cpath)=1 AND
            NOT EXISTS(SELECT list FROM users_workspaces_asset WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwa.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_asset WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

            $condition = 'IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';

            $this->addConditionParam($condition);
        }

        return $this;
    }
}
