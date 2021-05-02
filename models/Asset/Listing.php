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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Model\Paginator\PaginateListingInterface;

/**
 * @method Model\Asset[] load()
 * @method Model\Asset current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \Pimcore\Model\Asset\Listing\Dao getDao()
 * @method onCreateQuery(callable $callback)
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
     * @return static
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
    public function count()
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
     *
     * @return static
     */
    public function filterAccessibleByUser(Model\User $user)
    {
        if (!$user->isAdmin()) {
            $userIds = $user->getRoles();
            $userIds[] = $user->getId();

            $condition = '(
                (SELECT list FROM users_workspaces_asset WHERE userId IN ('.implode(',', $userIds).') AND LOCATE(CONCAT(path,filename),cpath)=1 ORDER BY LENGTH(cpath) DESC, FIELD(userId, '.$user->getId().') DESC, list DESC LIMIT 1)=1
                or
                (SELECT list FROM users_workspaces_asset WHERE userId IN ('.implode(',', $userIds).') AND LOCATE(cpath,CONCAT(path,filename))=1 ORDER BY LENGTH(cpath) DESC, FIELD(userId, '.$user->getId().') DESC, list DESC LIMIT 1)=1
            )';

            $this->addConditionParam($condition);
        }

        return $this;
    }
}
