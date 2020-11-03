# Users and Roles

## General

User permissions in Pimcore are based on a users and roles concept. Each user can have serveral roles and both - users
 and roles - can have permissions. 
 
Users and roles are configured in Pimcore backend UI at *Settings* > *Users & Roles* > *Users* and
 *Settings* > *Users & Roles* > *Roles*. 


## Permissions
In Pimcore there are two levels of user permissions. Firstly, the permissions on system components and secondly 
permissions on data elements (assets, objects and documents). Permissions can be granted to roles or individual users. 
The following paragraphs describe how and where permissions can be set and how they will or will not affect each other.

It is not mandatory to use roles, permissions can be granted to users directly. However, it is advised to use roles if 
there is a larger user group to manage. In the user/role settings tab it can be decided which permissions are granted 
to that user or role. An individual user has a few more general settings than the role.

### System Permissions

* Admin - if checked, all permissions on all system components are granted to that user
* Show welcome screen on startup
* Show close warning
* Roles - Select all roles incorporated by the user
* Perspectives - Which [perspectives](../18_Tools_and_Features/13_Perspectives.md) are available for this user

The following list outlines what the different system permissions (available for users and roles) mean:

* **Assets**: assets tree is visible
* **Classes**: Object classes editor visible (user can create and modify object classes)
* **Clear Cache**: defines if a user may clear Pimcore cache (internal cache and response cache if configured)
* **Clear Temporary Files**: defines if user may delete temporary system files (e.g thumbnails)
* **Dashboards**: User can create Dashboards
* **Documents**: documents tree is visible
* **Document Types**: User can create and modify predefined document types
* **Emails**: User sees E-Mail history
* **Extensions**: specifies if a user is allowed to download install and manage extension
* **Glossary**: Glossary entries visible
* **HTTP Errors**: HTTP Errors are visible 
* **Notes & Events**: Notes & Events are visible 
* **Objects**: objects tree is visible 
* **Predefined Properties**: User can create and modify predefined properties
* **QR-Codes**: User can create and modify QR codes
* **Recycle Bin**: User has access to recycle bin
* **Redirects**: User can create and modify redirects
* **Reports**: User has access to reports module
* **Seemode**: Seemode available/not available for user
* **SEO Document Editor**: User has access to SEO document editor
* **System Settings**: User has access to system settings
* **Tag & Snippet Management**: User can create and modify entries in tag & snippet management
* **Targeting**: User has access to targeting module
* **Thumbnails** User can create and modify thumbnail configurations
* **Translations**: defines whether a user may view and edit website translations
* **Users**: defines whether a user may manage other users' settings and system permissions
* **Website Settings**: User can create and modify website settings

A user will be granted any system permission that is granted to them directly or to any role they incorporate. 
A permission granted to a role incorporated by an individual user, can not be rescinded for that individual user. So it 
does not matter if the checkbox in the user's individual permissions settings is unchecked once a permission is granted 
through a role.

### Element Permissions - Workspaces

Beyond the permissions mentioned above, a user's access can be restricted on element basis. This can be done by defining 
workspaces for a user or role. Provided that a user may generally access documents, it can be specified what a user / role 
may do or not do with each document or workspace. The same is true for objects and assets. These settings are manipulated 
in the "Workspaces" tab of a user / role. 

A user needs to be granted access to one or more workspaces. The user can not access any resources outside of his workspace(s). 

However, there are a few general rules on element permissions which need to be regarded:
* if a user does not have the right to list an element, all other permissions are obsolete
* if a user does not have the list permission on an element, all permissions on this element's children are obsolete

![User permission workspaces](../img/permissions1.png)

The user permissions on element basis are summed up as follows:

* **list**: element can be listed in tree
* **view**: element can be opened
* **save**: element can be saved (save button visible)
* **publish**: element can be published (publish button visible)
* **unpublish**: element can be unpublished (unpublish button visible); does not exist for assets
* **create**: new child elements can be created (does not exist for assets)
* **delete**: element can be deleted
* **rename**: name of the element can be changed
* **settings**: element's settings can be managed i.e. the settings tab is visible; the settings permission also the path and thereby the right to move the element in tree
* **versions**: versions tab available
* **properties**: properties tab available and can be managed

An individual user is granted access to all defined workspaces for any role they incorporate. In addition to that, users 
can have their own workspaces. These are added to the permissions granted by roles.

For example, a role `myRole` has been granted list and view access to `/home/myPath`. The user editor incorporates the 
role `myRole` and thereby inherits all workspace settings from the role. 
In case the editor has his own workspace settings on `/home/myPath`, these permissions are added to permissions from any 
role they incorporate. A permission granted by any role can not be rescinded for a single user.


It is also possible to restrict access to localized fields on a language-level. By default, a user can view and edit 
(as long as they also have sufficient object permissions) all localized fields. This can now be restricted to a subset of 
languages. 

The configuration panel is accessible via the Special Settings column. The same dialog also allows to specify available
custom layouts for the user. 

![User permission workspaces](../img/permissions2.png)

#### Dynamically control permissions on elements

By using the event `Pimcore\Event\AdminEvents::ELEMENT_PERMISSION_IS_ALLOWED` it is possible to dynamically manipulate 
the permissions of a user on a specific element on request.
