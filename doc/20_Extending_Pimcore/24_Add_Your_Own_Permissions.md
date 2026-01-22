# Add Your Own Permissions

## Add your permission to the database
Permissions are stored in the `users_permission_definitions` table in your database. Use the `Definition` model to create your custom permission:

```php
use Pimcore\Model\User\Permission\Definition;

// Create your custom permission
$permissionKey = 'my_custom_permission';
$existingPermission = Definition::getByKey($permissionKey);

if (null === $existingPermission) {
    $permission = new Definition();
    $permission->setKey($permissionKey);
    $permission->setCategory('Custom Permission Group'); // Group related permissions
    $permission->save();
}
```
You should now be able to select the permission in the users/roles tabs:
![CustomPermissionPimcore](../img/custom_permissions_pimcore.png)

## Verify the permission

### Inside an AdminController
```php
namespace App\Controller;

use Pimcore\Controller\UserAwareController;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/admin/my-admin-action')]
    public function myAdminAction(Request $request): Response
    {
        $pimcoreUser = $this->getPimcoreUser();

        if ($pimcoreUser?->isAllowed('my_permission')) {
            // ...
        }
        
        return $this->jsonResponse(['success' => true]);
    }
}
```

### In the frontend (bundle)
```js
document.addEventListener(pimcore.events.pimcoreReady, (e) => {
    if(pimcore.currentuser.permissions.indexOf("my_permission") >= 0) {
        //...
    }
});
```
