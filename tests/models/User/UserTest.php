<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 15.07.2010
 * Time: 21:35:05
 */


class User_UserTest extends PHPUnit_Framework_TestCase {

    /**
     * create a user group
     */
    public function testCreateUserGroup() {

        $user = User::create(array(
            "parentId" => 0,
            "username" => "unitTestUserGroup",
            "password" => md5("unitTestUserGroup"),
            "hasCredentials" => false,
            "active" => true
        ));
        unset($user);
        $user = User::getByName("unitTestUserGroup");
        $this->assertTrue($user instanceof User and $user->getUsername() == "unitTestUserGroup");
    }


    /**
     * create a user
     * @depends testCreateUserGroup
     */
    public function testCreateUser() {

        $group = User::getByName("unitTestUserGroup");
        $user = new User();
        $user->setUsername("unitTestUser");
        $user->setParentId($group->getId());
        $user->setHasCredentials(true);
        $user->setPassword(md5("unitTestUser"));
        $user->save();

        unset($user);
        $user = User::getByName("unitTestUser");
        $this->assertTrue($user instanceof User and $user->getUsername() == "unitTestUser");
    }


    /**
     * makes sure you cannot create two users with the same user name
     * @expectedException Zend_Db_Statement_Exception
     */
    public function testDuplicateUserNames() {

        $user = new User();
        $user->setUsername("duplicateUser");
        $user->save();

        $user = new User();
        $user->setUsername("duplicateUser");
        $user->save();
    }


    /**
     * @depends testDuplicateUserNames
     */
    public function testCleanupDuplicate() {

        $user = User::getByName("duplicateUser");
        if ($user instanceof User) {
            $user->delete();
        }

    }

    /**
     * @depends testCreateUser
     */
    public function testModifyUserToAdmin() {
        $user = User::getByName("unitTestUser");
        $user->setUsername("newUnitTestUser");
        $user->setFirstname("firstname");
        $user->setLastname("lastname");
        $user->setEmail("email");
        $user->setLanguage("en");
        $user->setAdmin(true);
        $user->save();

        unset($user);
        $user = User::getByName("newUnitTestUser");
        $this->assertTrue($user instanceof User);
        $this->assertEquals("newUnitTestUser", $user->getUsername());
        $this->assertEquals("firstname", $user->getFirstname());
        $this->assertEquals("lastname", $user->getLastname());
        $this->assertEquals("email", $user->getEmail());
        $this->assertEquals("en", $user->getLanguage());
        $this->assertTrue($user->isAdmin());

        //test if admin is allowed all
        $permissionList = new User_Permission_Definition_List();
        $permissionList->load();
        $permissions = $permissionList->getDefinitions();
        foreach ($permissions as $permission) {
            $this->assertTrue($user->isAllowed($permission));
        }

        $user->setUsername("unitTestUser");
        $user->save();
    }

    /**
     * change general user permissions
     * @depends testModifyUserToAdmin
     * @var User $user
     */
    public function testPermissionChanges() {
        $userGroup = User::getByName("unitTestUserGroup");
        $username = $userGroup->getUsername();
        $userGroup->setAdmin(false);
        $userGroup->save();
        unset($userGroup);

        $userGroup = User::getByName($username);
        //test if admin is allowed all
        $permissionList = new User_Permission_Definition_List();
        $permissionList->load();
        $permissions = $permissionList->getDefinitions();


        $setPermissions = array();
        //gradually set all system permissions
        foreach ($permissions as $permission) {
            $userGroup->setPermission($permission->getKey());
            $setPermissions[] = $permission->getKey();
            $userGroup->save();
            unset($userGroup);
            $userGroup = User::getByName($username);
            foreach ($setPermissions as $p) {
                $this->assertTrue($userGroup->isAllowed($p));
            }
        }

        //remove system permissions
        $userGroup->setAllAclToFalse();
        foreach ($setPermissions as $p) {
            $this->assertFalse($userGroup->isAllowed($p));
        }

        //cannot list documents, assts, objects because no permissions by now

        $documentRoot = Document::getById(1);
        $documentRoot->getPermissionsForUser($userGroup);
        $this->assertFalse($documentRoot->isAllowed("list"));

        $objectRoot = Object_Abstract::getById(1);
        $objectRoot->getPermissionsForUser($userGroup);
        $this->assertFalse($objectRoot->isAllowed("list"));

        $assetRoot = Asset::getById(1);
        $assetRoot->getPermissionsForUser($userGroup);
        $this->assertFalse($assetRoot->isAllowed("list"));

        $objectFolder = new Object_Folder();
        $objectFolder->setParentId(1);
        $objectFolder->setUserOwner(1);
        $objectFolder->setUserModification(1);
        $objectFolder->setCreationDate(time());
        $objectFolder->setKey(uniqid() . rand(10, 99));
        $objectFolder->save();


        $documentFolder = Document_Folder::create(1, array(
            "userOwner" => 1,
            "key" => uniqid() . rand(10, 99)
        ));

        $assetFolder = Asset_Folder::create(1, array(
            "filename" => uniqid() . "_data",
            "type" => "folder",
            "userOwner" => 1
        ));

        $user = User::getByName("unitTestUser");
        $user->setAdmin(false);
        $user->save();

        $userGroup->setPermission("objects");
        $userGroup->setPermission("documents");
        $userGroup->setPermission("assets");
        $userGroup->save();

        //test permissions with user group and user
        $this->permissionTest($objectRoot, $objectFolder, $userGroup, $user, $user,"object");
        $this->permissionTest($assetRoot, $assetFolder, $userGroup, $user, $user,"asset");
        $this->permissionTest($documentRoot, $documentFolder, $userGroup, $user, $user, "document");

        //test permissions when there is no user group permissions
        $user = User::create(array(
            "parentId" => 0,
            "username" => "unitTestUser2",
            "password" => md5("unitTestUser2"),
            "hasCredentials" => true,
            "active" => true
        ));

        unset($user);
        $user = User::getByName("unitTestUser2");
        $user->setPermission("objects");
        $user->setPermission("documents");
        $user->setPermission("assets");
        $user->save();
        $this->assertTrue($user instanceof User and $user->getUsername() == "unitTestUser2");
        $this->permissionTest($objectRoot, $objectFolder, null, $user, $user, "object");
        $this->permissionTest($assetRoot, $assetFolder, null, $user, $user, "asset");
        $this->permissionTest($documentRoot, $documentFolder, null, $user, $user, "document");

        //test permissions when there is only user group permissions
        $user = User::create(array(
            "parentId" => $userGroup->getId(),
            "username" => "unitTestUser3",
            "password" => md5("unitTestUser3"),
            "hasCredentials" => true,
            "active" => true
        ));

        unset($user);
        $user = User::getByName("unitTestUser3");
        $this->assertTrue($user instanceof User and $user->getUsername() == "unitTestUser3");
        $this->permissionTest($objectRoot, $objectFolder, $userGroup, null, $user, "object");
        $this->permissionTest($assetRoot, $assetFolder, $userGroup, null, $user, "asset");
        $this->permissionTest($documentRoot, $documentFolder, $userGroup, null, $user, "document");
    }

    protected function permissionTest($parent, $element, $userGroup, $user, $userToTest,$type) {

        /*
        * test all possible element permission constellations
        * -----------------------------------------
           usergroup | user | parent list
           0		    0	    0		        0
           1		    0	    0		        0
           0		    1	    0		        0
           1		    1	    0		        0
           0		    0	    1		        0
           1		    0	    1		        1
           0		    1	    1		        1
           1		    1	    1		        1
        */

        //0 0 0
        $this->setPermissions($parent, $element, $userGroup, $user, false, false, false, $type);
        $element->getPermissionsForUser($userToTest);
        $this->assertFalse($element->isAllowed("list"));
        $this->assertFalse($element->isAllowed("view"));

        // 1 0 0
        if ($userGroup instanceof User and $user instanceof User) {
            $this->setPermissions($parent, $element, $userGroup, $user, true, false, false, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertFalse($element->isAllowed("list"));
            $this->assertFalse($element->isAllowed("view"));
        }
        
        // 0 1 0
        if ($user instanceof User) {
            //only relevant when there is a user
            $this->setPermissions($parent, $element, $userGroup, $user, false, true, false, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertFalse($element->isAllowed("list"));
            $this->assertFalse($element->isAllowed("view"));

        }

        // 1 1 0
        if($userGroup instanceof User){
            $this->setPermissions($parent, $element, $userGroup, $user, true, true, false, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertFalse($element->isAllowed("list"));
            $this->assertFalse($element->isAllowed("view"));
        }

        // 0 0 1
        $this->setPermissions($parent, $element, $userGroup, $user, false, false, true, $type);
        $element->getPermissionsForUser($userToTest);
        $this->assertFalse($element->isAllowed("list"));
        $this->assertFalse($element->isAllowed("view"));

        // 1 0 1
        if ($userGroup instanceof User) {
            //only relevant when we have a user group
            $this->setPermissions($parent, $element, $userGroup, $user, true, false, true, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertTrue($element->isAllowed("list"));
            $this->assertTrue($element->isAllowed("view"));
        }


        // 0 1 1
        if ($user instanceof User) {
            //only relevant when there is a user
            $this->setPermissions($parent, $element, $userGroup, $user, false, true, true, $type);
            $permission = $element->getPermissionsForUser($userToTest);
            $this->assertTrue($element->isAllowed("list"));
            $this->assertTrue($element->isAllowed("view"));
        }

        // 1 0 1
        if ($userGroup instanceof User) {
            //only relevant when we have a user group
            // 1 1 1
            $this->setPermissions($parent, $element, $userGroup, $user, true, true, true, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertTrue($element->isAllowed("list"));
            $this->assertTrue($element->isAllowed("view"));
        }

        //0 0 0 .. but then set user to admin
        if($user instanceof User){
            $this->setPermissions($parent, $element, $userGroup, $user, false, false, false, $type);
            $element->getPermissionsForUser($userToTest);
            $this->assertFalse($element->isAllowed("list"));
            $this->assertFalse($element->isAllowed("view"));
            $user->setAdmin(true);
            $user->save();
            $element->getPermissionsForUser($userToTest);
            $this->assertTrue($element->isAllowed("list"));
            $this->assertTrue($element->isAllowed("view"));
            $user->setAdmin(false);
            $user->save();
        }


        //no list - other permissions must be false
        if ($user instanceof User) {
            $this->setPermissions($parent, $element, $userGroup, $user, false, true, true, $type);
            $permissions = $element->getPermissionsForUser($user);
            $this->assertTrue($element->isAllowed("view"));
        } else {
            $this->setPermissions($parent, $element, $userGroup, $user, true, true, true, $type);
            $permissions = $element->getPermissionsForUser($userGroup);
            $this->assertTrue($element->isAllowed("view"));
        }
        $permissions->setList(false);
        $permissions->save();
        $permissions = $element->getPermissionsForUser($userToTest);
        $this->assertFalse($element->isAllowed("list"));
        $this->assertFalse($element->isAllowed("view"));

    }

    protected function setPermissions($parent, $element, $userGroup, $user, $groupAllowed, $userAllowed, $parentAllowed, $type) {

        $permissionClass = ucfirst($type) . "_Permissions";

        if ($user instanceof User) {
            $objectPermission = $parent->getPermissionsForUser($user);
            if ($objectPermission->getCpath() != $parent->getFullPath()) {
                $objectPermission = new $permissionClass();
                $objectPermission->setUser($user);
                $objectPermission->setUserId($user->getId());
                $objectPermission->setUsername($user->getUsername());
                $objectPermission->setCid($parent->getId());
                $objectPermission->setCpath($parent->getFullPath());
                $objectPermission->save();
            }
            $objectPermission->setList($parentAllowed);
            $objectPermission->setView($parentAllowed);
            $objectPermission->save();
        } else if ($userGroup instanceof User){
            $objectPermission = $parent->getPermissionsForUser($userGroup);
            if ($objectPermission->getCpath() != $parent->getFullPath()) {
                $objectPermission = new $permissionClass();
                $objectPermission->setUser($userGroup);
                $objectPermission->setUserId($userGroup->getId());
                $objectPermission->setUsername($userGroup->getUsername());
                $objectPermission->setCid($parent->getId());
                $objectPermission->setCpath($parent->getFullPath());
                $objectPermission->save();
            }
            $objectPermission->setList($parentAllowed);
            $objectPermission->setView($parentAllowed);
            $objectPermission->save();
        }


        if ($userGroup instanceof User) {
            $objectPermission = $element->getPermissionsForUser($userGroup);
            if ($objectPermission->getCpath() != $element->getFullPath()) {
                $objectPermission = new $permissionClass();
                $objectPermission->setUser($userGroup);
                $objectPermission->setUserId($userGroup->getId());
                $objectPermission->setUsername($userGroup->getUsername());
                $objectPermission->setCid($element->getId());
                $objectPermission->setCpath($element->getFullPath());
                $objectPermission->save();
                //echo "created new permission for element for user group \n";
            }
            $objectPermission->setList($groupAllowed);
            $objectPermission->setView($groupAllowed);
            $objectPermission->save();
        }


        if ($user instanceof User) {
            $objectPermission = $element->getPermissionsForUser($user);
            if ($objectPermission->getCpath() != $element->getFullPath()) {
                $objectPermission = new $permissionClass();
                $objectPermission->setUser($user);
                $objectPermission->setUserId($user->getId());
                $objectPermission->setUsername($user->getUsername());
                $objectPermission->setCid($element->getId());
                $objectPermission->setCpath($element->getFullPath());
                $objectPermission->save();
                $objectPermission = $element->getPermissionsForUser($user);
                //echo "created new permission for element for user \n";
            }
            $objectPermission->setList($userAllowed);
            $objectPermission->setView($userAllowed);
            $objectPermission->save();
        }


    }


    public function testDeleteUser() {
        $user = User::create(array(
            "parentId" => 0,
            "username" => "dummy",
            "password" => md5(time()),
            "hasCredentials" => true,
            "active" => true
        ));
        unset($user);
        $user = User::getByName("dummy");
        $user->delete();
        unset($user);
        $user = User::getByName("dummy");
        $this->assertFalse($user);


    }


    //TODO: Usergroup, system permission inheritance from user group,

    //TODO: element permissions 


}
