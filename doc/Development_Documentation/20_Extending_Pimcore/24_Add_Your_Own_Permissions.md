# Add your own permissions

## Add your permission to the database
Choose a custom unique name and add it to the `users_permission_definitions` table in your database.
You should now be able to select the permission in the users/roles tabs:
![CustomPermissionPimcore](../img/custom_permissions_pimcore.png)

## Verify the permission

### Inside an AdminController
```php
namespace AppBundle\Controller;


use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AdminController
{
    /**
     * @Route("/admin/my-admin-action")
     * @param Request $request
     */
    public function myAdminAction(Request $request) {

        /** @var \Pimcore\Bundle\AdminBundle\Security\User\User $user */
        $user = $this->getUser();
        $pimcoreUser = $user->getUser();

        if($pimcoreUser->isAllowed('my_permission')) {
            ...
        }
    }
}
```

### In the frontend (bundle)
```js
pimcore.registerNS("pimcore.plugin.AppBundle");

pimcore.plugin.AppBundle = Class.create(pimcore.plugin.admin, {
    pimcoreReady: function (params, broker) {

        if(pimcore.currentuser.permissions.indexOf("my_permission") >= 0) {
            ...
        }
    }
});
```
