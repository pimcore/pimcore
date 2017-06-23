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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\User;

class PermissionChecker
{
    public static function check(ElementInterface $element, $users)
    {
        $protectedColumns = ['cid', 'cpath', 'userId', 'lEdit', 'lView', 'layouts'];

        if ($element instanceof Object\AbstractObject) {
            $type = 'object';
        } else {
            if ($element instanceof Asset) {
                $type = 'asset';
            } else {
                if ($element instanceof Document) {
                    $type = 'document';
                } else {
                    throw new \Exception('type not supported');
                }
            }
        }
        $db = Db::get();
        $tableName = 'users_workspaces_'.$type;
        $tableDesc = $db->fetchAll('describe '.$tableName);

        $result = [
            'columns' => []
        ];

        foreach ($tableDesc as $column) {
            $columnName = $column['Field'];
            if (in_array($columnName, $protectedColumns)) {
                continue;
            }

            $result['columns'][] = $columnName;
        }

        $permissions = [];
        $details = [];

        /** @var $user User */
        foreach ($users as $user) {
            $userPermission = [];
            $userPermission['userId'] = $user->getId();
            $userPermission['userName'] = $user->getName();

            foreach ($result['columns'] as $columnName) {
                $parentIds = self::collectParentIds($element);

                $userIds = $user->getRoles();

                $userIds[] = $user->getId();

                if ($user->isAdmin()) {
                    $userPermission[$columnName] = true;
                    continue;
                }

                $userPermission[$columnName] = false;

                try {
                    $permissionsParent = $db->fetchRow(
                        'SELECT * FROM users_workspaces_'.$type.' , users u WHERE userId = u.id AND cid IN ('.implode(
                            ',',
                            $parentIds
                        ).') AND userId IN ('.implode(
                            ',',
                            $userIds
                        ).') ORDER BY LENGTH(cpath) DESC, ABS(userId-'.$user->getId().') ASC LIMIT 1'
                    );

                    if ($permissionsParent) {
                        $userPermission[$columnName] = $permissionsParent[$columnName] ? true : false;

                        $details[] = self::createDetail($user, $columnName, $userPermission[$columnName], $permissionsParent['type'], $permissionsParent['name'], $permissionsParent['cpath']);

                        continue;
                    }

                    // exception for list permission
                    if (empty($permissionsParent) && $columnName == 'list') {
                        // check for childs with permissions
                        $path = $element->getRealFullPath().'/';
                        if ($element->getId() == 1) {
                            $path = '/';
                        }

                        $permissionsChilds = $db->fetchRow(
                            'SELECT list FROM users_workspaces_'.$type.', users u WHERE userId = u.id AND cpath LIKE ? AND userId IN ('.implode(
                                ',',
                                $userIds
                            ).') AND list = 1 LIMIT 1',
                            $path.'%'
                        );
                        if ($permissionsChilds) {
                            $result[$columnName] = $permissionsChilds[$columnName] ? true : false;
                            $details[] = self::createDetail($user, $columnName, result[$columnName], $permissionsChilds['type'], $permissionsChilds['name'], $permissionsChilds['cpath']);
                            continue;
                        }
                    }
                } catch (\Exception $e) {
                    Logger::warn('Unable to get permission '.$type.' for object '.$element->getId());
                }
            }
            self::getUserPermissions($user, $details);
            self::getLanguagePermissions($user, $element, $details);
            $permissions[] = $userPermission;
        }

        $result['permissions'] = $permissions;

        $result['details'] = $details;

        return $result;
    }

    /**
     * @return array
     */
    protected static function collectParentIds($element)
    {
        // collect properties via parent - ids
        $parentIds = [1];

        $obj = $element->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        $parentIds[] = $element->getId();

        return $parentIds;
    }

    protected static function createDetail($user, $a = null, $b = null, $c = null, $d = null, $e = null, $f = null)
    {
        $detailEntry = [
            'userId' => $user->getId(),
            'a' => $a,
            'b' => $b,
            'c' => $c,
            'd' => $d,
            'e' => $e,
            'f' => $f

        ];

        return $detailEntry;
    }

    protected static function getUserPermissions($user, &$details)
    {
        if ($user->isAdmin()) {
            $details[] = self::createDetail($user, 'ADMIN', true, null, null);

            return;
        }
        $details[] = self::createDetail($user, '<b>User Permissions</b>', null, null, null);

        $db = Db::get();
        $permissions = $db->fetchCol('select `key` from users_permission_definitions');
        foreach ($permissions as $permissionKey) {
            $entry = null;

            if (!$user->getPermission($permissionKey)) {
                // check roles
                foreach ($user->getRoles() as $roleId) {
                    $role = User\Role::getById($roleId);
                    if ($role->getPermission($permissionKey)) {
                        $entry = self::createDetail($user, $permissionKey, true, $role->getType(), $role->getName());
                        break;
                    }
                }
            } else {
                $entry = self::createDetail($user, $permissionKey, true, $user->getType(), $user->getName());
            }

            if (!$entry) {
                $entry = self::createDetail($user, $permissionKey, false, null, null);
            }
            $details[] = $entry;
        }
    }

    /**
     * @param $user User\
     * @param $element
     * @param $details
     */
    protected function getLanguagePermissions($user, $element, &$details)
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($element instanceof Object\AbstractObject) {
            $details[] = self::createDetail($user, '<b>Language Permissions</b>', null, null, null);

            $permissions = ['lView' => 'view', 'lEdit' => 'edit'];
            foreach ($permissions as $permissionKey => $permissionName) {
                $languagePermissions = Object\Service::getLanguagePermissions($element, $user, $permissionKey);
                if (!$languagePermissions) {
                    $languagePermissions = 'all';
                } else {
                    $languagePermissions = array_keys($languagePermissions);
                    $languagePermissions = implode(', ', $languagePermissions);
                }

                $details[] = self::createDetail($user, $permissionName, null, null, null, $languagePermissions);
            }
        }
    }
}
