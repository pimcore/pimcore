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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Logger;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/user/tree-get-childs-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request)
    {
        $list = new User\Listing();
        $list->setCondition('parentId = ?', intval($request->get('node')));
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $users = [];
        if (is_array($list->getUsers())) {
            foreach ($list->getUsers() as $user) {
                if ($user->getId() && $user->getName() != 'system') {
                    $users[] = $this->getTreeNodeConfig($user);
                }
            }
        }

        return $this->json($users);
    }

    /**
     * @param $user
     *
     * @return array
     */
    protected function getTreeNodeConfig($user)
    {
        $tmpUser = [
            'id' => $user->getId(),
            'text' => $user->getName(),
            'elementType' => 'user',
            'type' => $user->getType(),
            'qtipCfg' => [
                'title' => 'ID: ' . $user->getId()
            ]
        ];

        // set type specific settings
        if ($user instanceof User\Folder) {
            $tmpUser['leaf'] = false;
            $tmpUser['iconCls'] = 'pimcore_icon_folder';
            $tmpUser['expanded'] = true;
            $tmpUser['allowChildren'] = true;

            if ($user->hasChildren()) {
                $tmpUser['expanded'] = false;
            } else {
                $tmpUser['loaded'] = true;
            }
        } else {
            $tmpUser['leaf'] = true;
            $tmpUser['iconCls'] = 'pimcore_icon_user';
            if (!$user->getActive()) {
                $tmpUser['cls'] = ' pimcore_unpublished';
            }
            $tmpUser['allowChildren'] = false;
            $tmpUser['admin'] = $user->isAdmin();
        }

        return $tmpUser;
    }

    /**
     * @Route("/user/add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $this->protectCsrf($request);

        try {
            $type = $request->get('type');

            $className = User\Service::getClassNameForType($type);
            $user = $className::create([
                'parentId' => intval($request->get('parentId')),
                'name' => trim($request->get('name')),
                'password' => '',
                'active' => $request->get('active')
            ]);

            if ($request->get('rid')) {
                $rid = $request->get('rid');
                $rObject = $className::getById($rid);
                if ($rObject) {
                    if ($type == 'user' || $type == 'role') {
                        $user->setParentId($rObject->getParentId());
                        if ($rObject->getClasses()) {
                            $user->setClasses(implode(',', $rObject->getClasses()));
                        }
                        if ($rObject->getDocTypes()) {
                            $user->setDocTypes(implode(',', $rObject->getDocTypes()));
                        }

                        $keys = ['asset', 'document', 'object'];
                        foreach ($keys as $key) {
                            $getter = 'getWorkspaces' . ucfirst($key);
                            $setter = 'setWorkspaces' . ucfirst($key);
                            $workspaces = $rObject->$getter();
                            $clonedWorkspaces = [];
                            if (is_array($workspaces)) {
                                foreach ($workspaces as $workspace) {
                                    $vars = get_object_vars($workspace);
                                    $workspaceClass = '\\Pimcore\\Model\\User\\Workspace\\' . ucfirst($key);
                                    $newWorkspace = new $workspaceClass();
                                    foreach ($vars as $varKey => $varValue) {
                                        $newWorkspace->$varKey = $varValue;
                                    }
                                    $newWorkspace->setUserId($user->getId());
                                    $clonedWorkspaces[] = $newWorkspace;
                                }
                            }

                            $user->$setter($clonedWorkspaces);
                        }

                        $user->setPermissions($rObject->getPermissions());

                        if ($type == 'user') {
                            $user->setAdmin(false);
                            if ($this->getUser()->isAdmin()) {
                                $user->setAdmin($rObject->getAdmin());
                            }
                            $user->setActive($rObject->getActive());
                            $user->setRoles($rObject->getRoles());
                            $user->setWelcomeScreen($rObject->getWelcomescreen());
                            $user->setMemorizeTabs($rObject->getMemorizeTabs());
                            $user->setCloseWarning($rObject->getCloseWarning());
                        }

                        $user->save();
                    }
                }
            }

            return $this->json([
                'success' => true,
                'id' => $user->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param $node
     * @param $currentList
     * @param $roleMode
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function populateChildNodes($node, &$currentList, $roleMode)
    {
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();

        $list = $roleMode ? new User\Role\Listing() : new User\Listing();
        $list->setCondition('parentId = ?', $node->getId());
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $childList = $roleMode ? $list->getRoles() : $list->getUsers();
        if (is_array($childList)) {
            foreach ($childList as $user) {
                if ($user->getId() == $currentUser->getId()) {
                    throw new \Exception('Cannot delete current user');
                }
                if ($user->getId() && $currentUser->getId() && $user->getName() != 'system') {
                    $currentList[] = $user;
                    $this->populateChildNodes($user, $currentList, $roleMode);
                }
            }
        }

        return $currentList;
    }

    /**
     * @Route("/user/delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        $user = User\AbstractUser::getById(intval($request->get('id')));

        // only admins are allowed to delete admins and folders
        // because a folder might contain an admin user, so it is simply not allowed for users with the "users" permission
        if (($user instanceof User\Folder && !$this->getUser()->isAdmin()) || ($user instanceof User && $user->isAdmin() && !$this->getUser()->isAdmin())) {
            throw new \Exception('You are not allowed to delete this user');
        } else {
            if ($user instanceof User\Role\Folder) {
                $list = [$user];
                $this->populateChildNodes($user, $list, $user instanceof User\Role\Folder);
                $listCount = count($list);
                for ($i = $listCount - 1; $i >= 0; $i--) {
                    // iterate over the list from the so that nothing can get "lost"
                    $user = $list[$i];
                    $user->delete();
                }
            } else {
                if ($user->getId()) {
                    if ($user instanceof User\Role) {
                        // #1431 remove user-role relations
                        $userRoleRelationListing = new User\Listing();
                        $userRoleRelationListing->setCondition('FIND_IN_SET(' . $user->getId() . ',roles)');
                        $userRoleRelationListing = $userRoleRelationListing->load();
                        if ($userRoleRelationListing) {
                            /** @var $relatedUser User */
                            foreach ($userRoleRelationListing as $relatedUser) {
                                $userRoles = $relatedUser->getRoles();
                                if (is_array($userRoles)) {
                                    $key = array_search($user->getId(), $userRoles);
                                    if (false !== $key) {
                                        unset($userRoles[$key]);
                                        $relatedUser->setRoles($userRoles);
                                        $relatedUser->save();
                                    }
                                }
                            }
                        }
                    }

                    $user->delete();
                }
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/user/update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $this->protectCsrf($request);

        $user = User\AbstractUser::getById(intval($request->get('id')));

        if ($user instanceof User && $user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception('Only admin users are allowed to modify admin users');
        }

        if ($request->get('data')) {
            $values = $this->decodeJson($request->get('data'), true);

            if (!empty($values['password'])) {
                $values['password'] = Tool\Authentication::getPasswordHash($user->getName(), $values['password']);
            }

            // check if there are permissions transmitted, if so reset them all to false (they will be set later)
            foreach ($values as $key => $value) {
                if (strpos($key, 'permission_') === 0) {
                    if (method_exists($user, 'setAllAclToFalse')) {
                        $user->setAllAclToFalse();
                    }
                    break;
                }
            }

            $user->setValues($values);

            // only admins are allowed to create admin users
            // if the logged in user isn't an admin, set admin always to false
            if (!$this->getUser()->isAdmin() && $user instanceof User) {
                if ($user instanceof User) {
                    $user->setAdmin(false);
                }
            }

            // check for permissions
            $availableUserPermissionsList = new User\Permission\Definition\Listing();
            $availableUserPermissions = $availableUserPermissionsList->load();

            foreach ($availableUserPermissions as $permission) {
                if (isset($values['permission_' . $permission->getKey()])) {
                    $user->setPermission($permission->getKey(), (bool) $values['permission_' . $permission->getKey()]);
                }
            }

            // check for workspaces
            if ($request->get('workspaces')) {
                $workspaces = $this->decodeJson($request->get('workspaces'), true);
                foreach ($workspaces as $type => $spaces) {
                    $newWorkspaces = [];
                    foreach ($spaces as $space) {
                        $element = Element\Service::getElementByPath($type, $space['path']);
                        if ($element) {
                            $className = '\\Pimcore\\Model\\User\\Workspace\\' . ucfirst($type);
                            $workspace = new $className();
                            $workspace->setValues($space);

                            $workspace->setCid($element->getId());
                            $workspace->setCpath($element->getRealFullPath());
                            $workspace->setUserId($user->getId());

                            $newWorkspaces[] = $workspace;
                        }
                    }
                    $user->{'setWorkspaces' . ucfirst($type)}($newWorkspaces);
                }
            }
        }

        $user->save();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/user/get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getAction(Request $request)
    {
        if (intval($request->get('id')) < 1) {
            return $this->json(['success' => false]);
        }

        $user = User::getById(intval($request->get('id')));

        if ($user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception('Only admin users are allowed to modify admin users');
        }

        // workspaces
        $types = ['asset', 'document', 'object'];
        foreach ($types as $type) {
            $workspaces = $user->{'getWorkspaces' . ucfirst($type)}();
            foreach ($workspaces as $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if ($el) {
                    // direct injection => not nice but in this case ok ;-)
                    $workspace->path = $el->getRealFullPath();
                }
            }
        }

        // object <=> user dependencies
        $userObjects = DataObject\Service::getObjectsReferencingUser($user->getId());
        $userObjectData = [];

        foreach ($userObjects as $o) {
            $hasHidden = false;
            if ($o->isAllowed('list')) {
                $userObjectData[] = [
                    'path' => $o->getRealFullPath(),
                    'id' => $o->getId(),
                    'subtype' => $o->getClass()->getName()
                ];
            } else {
                $hasHidden = true;
            }
        }

        // get available permissions
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissions = $availableUserPermissionsList->load();

        // get available roles
        $list = new User\Role\Listing();
        $list->setCondition('`type` = ?', ['role']);
        $list->load();

        $roles = [];
        if (is_array($list->getItems())) {
            foreach ($list->getItems() as $role) {
                $roles[] = [$role->getId(), $role->getName()];
            }
        }

        // unset confidential informations
        $userData = object2array($user);
        $contentLanguages = Tool\Admin::reorderWebsiteLanguages($user, Tool::getValidLanguages());
        $userData['contentLanguages'] = $contentLanguages;
        unset($userData['password']);

        $availablePerspectives = \Pimcore\Config::getAvailablePerspectives(null);

        $conf = \Pimcore\Config::getSystemConfig();

        return $this->json([
            'success' => true,
            'wsenabled' => $conf->webservice->enabled,
            'user' => $userData,
            'roles' => $roles,
            'permissions' => $user->generatePermissionList(),
            'availablePermissions' => $availableUserPermissions,
            'availablePerspectives' => $availablePerspectives,
            'validLanguages' => Tool::getValidLanguages(),
            'objectDependencies' => [
                'hasHidden' => $hasHidden,
                'dependencies' => $userObjectData
            ]
        ]);
    }

    /**
     * @Route("/user/get-minimal")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getMinimalAction(Request $request)
    {
        $user = User::getById(intval($request->get('id')));
        $user->setPassword(null);

        $minimalUserData['id'] = $user->getId();
        $minimalUserData['admin'] = $user->isAdmin();
        $minimalUserData['active'] = $user->isActive();
        $minimalUserData['permissionInfo']['assets'] = $user->isAllowed('assets');
        $minimalUserData['permissionInfo']['documents'] = $user->isAllowed('documents');
        $minimalUserData['permissionInfo']['objects'] = $user->isAllowed('objects');

        return $this->json($minimalUserData);
    }

    /**
     * @Route("/user/upload-current-user-image")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadCurrentUserImageAction(Request $request)
    {
        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $request->get('id')) {
                $this->uploadImageAction();
            } else {
                Logger::warn('prevented save current user, because ids do not match. ');

                return $this->json(false);
            }
        } else {
            return $this->json(false);
        }
    }

    /**
     * @Route("/user/update-current-user")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateCurrentUserAction(Request $request)
    {
        $this->protectCsrf($request);

        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $request->get('id')) {
                $values = $this->decodeJson($request->get('data'), true);

                unset($values['name']);
                unset($values['id']);
                unset($values['admin']);
                unset($values['permissions']);
                unset($values['roles']);
                unset($values['active']);

                if (!empty($values['new_password'])) {
                    $oldPasswordCheck = false;

                    if (empty($values['old_password'])) {
                        // if the user want to reset the password, the old password isn't required
                        $oldPasswordCheck = Tool\Session::useSession(function (AttributeBagInterface $adminSession) use ($oldPasswordCheck) {
                            if ($adminSession->get('password_reset')) {
                                return true;
                            }

                            return false;
                        });
                    } else {
                        // the password has to match
                        $checkUser = Tool\Authentication::authenticatePlaintext($user->getName(), $values['old_password']);
                        if ($checkUser) {
                            $oldPasswordCheck = true;
                        }
                    }

                    if ($oldPasswordCheck && $values['new_password'] == $values['retype_password']) {
                        $values['password'] = Tool\Authentication::getPasswordHash($user->getName(), $values['new_password']);
                    } else {
                        return $this->json(['success' => false, 'message' => 'password_cannot_be_changed']);
                    }
                }

                $user->setValues($values);
                $user->save();

                return $this->json(['success' => true]);
            } else {
                Logger::warn('prevented save current user, because ids do not match. ');

                return $this->json(false);
            }
        } else {
            return $this->json(false);
        }
    }

    /**
     * @Route("/user/get-current-user")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getCurrentUserAction(Request $request)
    {
        $user = $this->getUser();

        $list = new User\Permission\Definition\Listing();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $user->setPermission($definition->getKey(), $user->isAllowed($definition->getKey()));
        }

        // unset confidential informations
        $userData = object2array($user);
        $contentLanguages = Tool\Admin::reorderWebsiteLanguages($user, Tool::getValidLanguages());
        $userData['contentLanguages'] = $contentLanguages;
        unset($userData['password']);

        $response = new Response('pimcore.currentuser = ' . $this->encodeJson($userData));
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /* ROLES */

    /**
     * @Route("/user/role-tree-get-childs-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function roleTreeGetChildsByIdAction(Request $request)
    {
        $list = new User\Role\Listing();
        $list->setCondition('parentId = ?', intval($request->get('node')));
        $list->load();

        $roles = [];
        if (is_array($list->getItems())) {
            foreach ($list->getItems() as $role) {
                $roles[] = $this->getRoleTreeNodeConfig($role);
            }
        }

        return $this->json($roles);
    }

    /**
     * @param $role
     *
     * @return array
     */
    protected function getRoleTreeNodeConfig($role)
    {
        $tmpUser = [
            'id' => $role->getId(),
            'text' => $role->getName(),
            'elementType' => 'role',
            'qtipCfg' => [
                'title' => 'ID: ' . $role->getId()
            ]
        ];

        // set type specific settings
        if ($role instanceof User\Role\Folder) {
            $tmpUser['leaf'] = false;
            $tmpUser['iconCls'] = 'pimcore_icon_folder';
            $tmpUser['expanded'] = true;
            $tmpUser['allowChildren'] = true;

            if ($role->hasChildren()) {
                $tmpUser['expanded'] = false;
            } else {
                $tmpUser['loaded'] = true;
            }
        } else {
            $tmpUser['leaf'] = true;
            $tmpUser['iconCls'] = 'pimcore_icon_roles';
            $tmpUser['allowChildren'] = false;
        }

        return $tmpUser;
    }

    /**
     * @Route("/user/role-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function roleGetAction(Request $request)
    {
        $role = User\Role::getById(intval($request->get('id')));

        // workspaces
        $types = ['asset', 'document', 'object'];
        foreach ($types as $type) {
            $workspaces = $role->{'getWorkspaces' . ucfirst($type)}();
            foreach ($workspaces as $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if ($el) {
                    // direct injection => not nice but in this case ok ;-)
                    $workspace->path = $el->getRealFullPath();
                }
            }
        }

        // get available permissions
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissions = $availableUserPermissionsList->load();

        $availablePerspectives = \Pimcore\Config::getAvailablePerspectives(null);

        return $this->json([
            'success' => true,
            'role' => $role,
            'permissions' => $role->generatePermissionList(),
            'classes' => $role->getClasses(),
            'docTypes' => $role->getDocTypes(),
            'availablePermissions' => $availableUserPermissions,
            'availablePerspectives' => $availablePerspectives,
            'validLanguages' => Tool::getValidLanguages()
        ]);
    }

    /**
     * @Route("/user/upload-image")
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function uploadImageAction(Request $request)
    {
        if ($request->get('id')) {
            if ($this->getUser()->getId() != $request->get('id')) {
                $this->checkPermission('users');
            }
            $id = $request->get('id');
        } else {
            $id = $this->getUser()->getId();
        }

        $userObj = User::getById($id);

        if ($userObj->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception('Only admin users are allowed to modify admin users');
        }

        $userObj->setImage($_FILES['Filedata']['tmp_name']);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed

        $response = $this->json(['success' => true]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/user/get-image")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function getImageAction(Request $request)
    {
        if ($request->get('id')) {
            if ($this->getUser()->getId() != $request->get('id')) {
                $this->checkPermission('users');
            }
            $id = $request->get('id');
        } else {
            $id = $this->getUser()->getId();
        }

        /** @var User $userObj */
        $userObj = User::getById($id);
        $thumb = $userObj->getImage();

        $response = new BinaryFileResponse($thumb);
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * @Route("/user/get-token-login-link")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getTokenLoginLinkAction(Request $request)
    {
        $user = User::getById($request->get('id'));

        if ($user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception('Only admin users are allowed to login as an admin user');
        }

        if ($user) {
            $token = Tool\Authentication::generateToken($user->getName(), $user->getPassword());

            $link = $request->getScheme() . '://' . $request->getHttpHost() . '/admin/login/login?username=' . $user->getName() . '&token=' . $token;

            return $this->json([
                'link' => $link
            ]);
        }
    }

    /**
     * @Route("/user/search")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $q = '%' . $request->get('query') . '%';

        $list = new User\Listing();
        $list->setCondition('name LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR id = ?', [$q, $q, $q, $q, intval($request->get('query'))]);
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $users = [];
        if (is_array($list->getUsers())) {
            foreach ($list->getUsers() as $user) {
                if ($user instanceof User && $user->getId() && $user->getName() != 'system') {
                    $users[] = [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                    ];
                }
            }
        }

        return $this->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        // check permissions
        $unrestrictedActions = [
            'getCurrentUserAction', 'updateCurrentUserAction', 'getAvailablePermissionsAction', 'getMinimalAction',
            'getImageAction', 'uploadCurrentUserImageAction'
        ];

        $this->checkActionPermission($event, 'users', $unrestrictedActions);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
