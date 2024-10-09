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

namespace Pimcore\Model\Element;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\User;

/**
 * @internal
 *
 * @property Model\Document|Model\Asset|Model\DataObject\AbstractObject $model
 */
abstract class Dao extends Model\Dao\AbstractDao
{
    /**
     * @return int[]
     *
     * @throws Exception
     */
    public function getParentIds(): array
    {
        // collect properties via parent - ids
        $parentIds = [1];
        $obj = $this->model->getParent();

        if ($obj) {
            while ($obj) {
                if ($obj->getId() == 1) {
                    break;
                }
                if (in_array($obj->getId(), $parentIds)) {
                    throw new Exception('detected infinite loop while resolving all parents from ' . $this->model->getId() . ' on ' . $obj->getId());
                }

                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }

        return $parentIds;
    }

    protected function extractKeyAndPath(string $fullpath): array
    {
        $key = '';
        $path = $fullpath;
        if ($fullpath !== '/') {
            $lastPart = strrpos($fullpath, '/') + 1;
            $key = substr($fullpath, $lastPart);
            $path = substr($fullpath, 0, $lastPart);
        }

        return [
            'key' => $key,
            'path' => $path,
        ];
    }

    abstract public function getVersionCountForUpdate(): int;

    /**
     *
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function InheritingPermission(string $type, array $userIds, string $tableSuffix): int
    {
        $current = $this->model;

        if (!$current->getId()) {
            return 0;
        }
        $fullPath = $current->getPath() . $current->getKey();

        $sql = 'SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_' . $tableSuffix . ' WHERE LOCATE(cpath, ?)=1 AND
        userId IN (' . implode(',', $userIds) . ')
        ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . end($userIds) . ') DESC, ' . $this->db->quoteIdentifier($type) . ' DESC LIMIT 1';

        return (int)$this->db->fetchOne($sql, [$fullPath]);
    }

    /**
     * @param string[] $columns
     *
     * @return array<string, int>
     *
     * @internal
     */
    protected function permissionByTypes(array $columns, User $user, string $tableSuffix): array
    {
        $permissions = [];
        foreach ($columns as $type) {
            $permissions[$type] = 0;
        }

        $parentIds = $this->getParentIds();
        if ($id = $this->model->getId()) {
            $parentIds[] = $id;
        }

        $currentUserId = $user->getId();
        $userIds = $user->getRoles();
        $userIds[] = $currentUserId;

        $highestWorkspaceQuery = '
            SELECT userId,cid,`'. implode('`,`', $columns) .'` FROM users_workspaces_'.$tableSuffix.'
            WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ')
            ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $currentUserId . ') DESC LIMIT 1
        ';

        $highestWorkspace = $this->db->fetchAssociative($highestWorkspaceQuery);

        if ($highestWorkspace) {
            //if it's the current user, this is the permission that rules them all, no need to check others
            if ($highestWorkspace['userId'] == $currentUserId) {
                foreach ($columns as $type) {
                    $permissions[$type] = (int) $highestWorkspace[$type];
                }

                if ($permissions['list'] == 0) {
                    $permissions['list'] = $this->checkChildrenForPathTraversal($tableSuffix, $userIds);
                }

                return $permissions;
            }

            //if not found, having already the longest cpath from first query,
            //we either have role permission for the same object, or it could be any of its parents permission.

            $roleWorkspaceSql = '
             SELECT userId,`'. implode('`,`', $columns) .'` FROM users_workspaces_'.$tableSuffix.'
             WHERE cid = ' . $highestWorkspace['cid'] . ' AND userId IN (' . implode(',', $userIds) . ')
             ORDER BY FIELD(userId, ' . $currentUserId . ') DESC
             ';
            $objectPermissions = $this->db->fetchAllAssociative($roleWorkspaceSql);

            //this performs the additive rule when conflicting rules with multiple roles,
            //breaks the loop when permission=1 is found and move on to check next permission type.
            foreach ($columns as $type) {
                foreach ($objectPermissions as $workspace) {
                    if ($workspace[$type] == 1) {
                        $permissions[$type] = 1;

                        break;
                    }
                }
            }
        }

        //when list=0, we look for any allowed children, so that can make possible to list the path of the folder in between
        //to reach that children by "exceptionally" turning list=0 to list=1
        if ($permissions['list']==0) {
            $permissions['list'] = $this->checkChildrenForPathTraversal($tableSuffix, $userIds);
        }

        return $permissions;
    }

    /**
     * for "path traversal" intending the list=1 on parent folder (with list=0) when there are nested children allowed
     */
    private function checkChildrenForPathTraversal(string $tableSuffix, array $userIds): int
    {
        $path = $this->model->getId() == 1 ? '/' : $this->model->getRealFullPath() . '/';

        $permissionsChildren = $this->db->fetchOne('
            SELECT list FROM users_workspaces_'.$tableSuffix.' as uw
            WHERE cpath LIKE ? AND userId IN (' . implode(',', $userIds) . ') AND list = 1
            AND NOT EXISTS( SELECT list FROM users_workspaces_'.$tableSuffix.' WHERE cid = uw.cid AND list = 0 AND userId ='.end($userIds).')
            LIMIT 1',
            [Helper::escapeLike($path) . '%']);

        return (int)$permissionsChildren;
    }
}
