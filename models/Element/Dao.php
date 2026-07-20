<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Element;

use Doctrine\DBAL\ArrayParameterType;
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

        // A workspace applies to this element when its cpath is the element itself or one of its
        // ancestors. Matching the exact set of ancestor paths (instead of LOCATE(cpath, fullPath),
        // which matches on a raw substring and therefore also matched unrelated siblings sharing a
        // path prefix, e.g. "/foo/Car" matching "/foo/Carpets/...") is both boundary-correct and
        // index-usable on users_workspaces_*.cpath.
        $paths = $this->getAncestorPaths($fullPath);

        $sql = 'SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_' . $tableSuffix . ' WHERE cpath IN (:paths) AND
        userId IN (:userIds)
        ORDER BY LENGTH(cpath) DESC, FIELD(userId, :lastUserId) DESC, ' . $this->db->quoteIdentifier($type) . ' DESC LIMIT 1';

        return (int)$this->db->fetchOne(
            $sql,
            [
                'paths' => $paths,
                'userIds' => $userIds,
                'lastUserId' => end($userIds),
            ],
            [
                'paths' => ArrayParameterType::STRING,
                'userIds' => ArrayParameterType::INTEGER,
            ]
        );
    }

    /**
     * Returns the element's own full path and the full paths of all its ancestors, including the
     * root path "/". Used for exact, boundary-correct workspace matching against cpath.
     *
     * @return string[]
     */
    protected function getAncestorPaths(string $fullPath): array
    {
        $paths = ['/'];
        $current = '';
        foreach (explode('/', trim($fullPath, '/')) as $segment) {
            if ($segment === '') {
                continue;
            }
            $current .= '/' . $segment;
            $paths[] = $current;
        }

        return array_values(array_unique($paths));
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

    /**
     * Builds a boundary-correct, index-usable "listable child" WHERE fragment (against the `o`
     * alias of the element table) for the children of the current folder, together with its bound
     * parameters and types.
     *
     * A child is listable when the user has an allowed (list=1, not user-denied) workspace on the
     * child itself or anywhere in its subtree, or when the folder inherits the list permission and
     * the child is not explicitly denied. The subtree check is resolved with a single folder-scoped
     * lookup (a constant cpath prefix, so the cpath index range applies) instead of a
     * LOCATE(cpath) correlated subquery evaluated per child — turning an
     * O(children * workspace-rows) scan into O(children + workspace-rows).
     *
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, ArrayParameterType>}
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function buildChildListPermissionCondition(User $user, string $tableSuffix, string $keyColumn): array
    {
        $userIds = $user->getRoles();
        $userIds[] = $user->getId();
        $currentUserId = $user->getId();
        $table = 'users_workspaces_' . $tableSuffix;

        $folderPath = $this->model->getRealFullPath();
        $childPrefix = $folderPath === '/' ? '/' : $folderPath . '/';

        // the user's allowed (list=1) workspace paths anywhere in this folder's subtree, excluding
        // paths the current user is explicitly denied on — a single sargable range on cpath
        $allowedPaths = $this->db->fetchFirstColumn(
            'SELECT uw.cpath FROM ' . $table . ' uw
             WHERE uw.userId IN (:userIds) AND uw.list = 1 AND uw.cpath LIKE :childPrefix
               AND NOT EXISTS(
                   SELECT 1 FROM ' . $table . ' denied
                   WHERE denied.userId = :currentUserId AND denied.list = 0 AND denied.cpath = uw.cpath
               )',
            [
                'userIds' => $userIds,
                'childPrefix' => Helper::escapeLike($childPrefix) . '%',
                'currentUserId' => $currentUserId,
            ],
            ['userIds' => ArrayParameterType::INTEGER]
        );

        $allowedChildKeys = $this->immediateChildSegments($childPrefix, $allowedPaths);

        $conditions = [];
        $params = [];
        $types = [];

        if ($allowedChildKeys !== []) {
            $conditions[] = 'o.' . $this->db->quoteIdentifier($keyColumn) . ' IN (:allowedChildKeys)';
            $params['allowedChildKeys'] = $allowedChildKeys;
            $types['allowedChildKeys'] = ArrayParameterType::STRING;
        }

        // when the folder inherits list, a child is listable unless it is explicitly denied
        if ($this->InheritingPermission('list', $userIds, $tableSuffix) !== 0) {
            $conditions[] = 'NOT EXISTS(
                SELECT 1 FROM ' . $table . ' deniedChild
                WHERE deniedChild.userId IN (:deniedUserIds) AND deniedChild.cid = o.id AND deniedChild.list = 0
            )';
            $params['deniedUserIds'] = $userIds;
            $types['deniedUserIds'] = ArrayParameterType::INTEGER;
        }

        // no allowed subtree and no inherited permission -> no child is listable
        $condition = $conditions === [] ? '0' : '(' . implode(' OR ', $conditions) . ')';

        return [$condition, $params, $types];
    }

    /**
     * Extracts the distinct immediate child segment directly under $childPrefix from a set of
     * descendant cpaths (e.g. "/a/b/" + "/a/b/c/d" -> "c").
     *
     * @param string[] $cpaths
     *
     * @return string[]
     */
    private function immediateChildSegments(string $childPrefix, array $cpaths): array
    {
        $prefixLength = strlen($childPrefix);
        $segments = [];
        foreach ($cpaths as $cpath) {
            if (!str_starts_with($cpath, $childPrefix)) {
                continue;
            }
            $rest = substr($cpath, $prefixLength);
            $slash = strpos($rest, '/');
            $segment = $slash === false ? $rest : substr($rest, 0, $slash);
            if ($segment !== '') {
                $segments[$segment] = true;
            }
        }

        return array_keys($segments);
    }
}
