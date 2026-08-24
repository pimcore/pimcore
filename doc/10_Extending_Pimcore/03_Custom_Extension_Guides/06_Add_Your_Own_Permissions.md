---
title: Add Your Own Permissions
description: Register custom permissions and check them in Studio Backend and Studio UI.
---

# Add Your Own Permissions

## Register the Permission

Create a custom permission key using the `Definition` model. Pimcore stores permissions in the
`users_permission_definitions` table:

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
![CustomPermissionPimcore](../../img/custom_permissions_pimcore.png)

## Verify the permission

### Studio Backend (PHP controller)

Use Symfony's `#[IsGranted]` attribute on your Studio Backend controller actions. The `UserPermissionVoter` 
automatically picks up all keys from `users_permission_definitions`, so no extra registration is needed:

```php
namespace App\Controller;

use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MyCustomController extends AbstractApiController
{
    #[Route('/studio/api/my-custom-action', methods: ['GET'])]
    #[IsGranted('my_custom_permission')]
    public function myCustomAction(): JsonResponse
    {
        // Only users with 'my_custom_permission' can reach this action.
        // Admins are always allowed.
        return new JsonResponse(['success' => true]);
    }
}
```

:::note
The `UserPermissionVoter` in `studio-backend-bundle` reads every key from `users_permission_definitions` at runtime. Any custom permission you add to that table is immediately usable with `#[IsGranted]`. No voter or config registration required.
:::

### Studio Frontend (plugin)

Import `isAllowed` or `getCurrentUser` from the Studio UI SDK's auth module:

```typescript
import { isAllowed, getCurrentUser } from '@pimcore/studio-ui-bundle/modules/auth'

// Simple boolean check - returns true for admins automatically
if (isAllowed('my_custom_permission')) {
  // User has the permission
}

// Access the full user object for more detailed checks
const user = getCurrentUser()
console.log(user.permissions) // string[] of granted permission keys
console.log(user.isAdmin)     // boolean
```

:::note
`getCurrentUser()` and `isAllowed()` read from the Redux store. They are available once the user is
authenticated. Use them in React components or event handlers, not during plugin `onStartup`.
:::

Inside a React component, use the `useUser` hook instead:

```tsx
import { useUser } from '@pimcore/studio-ui-bundle/modules/auth'

function MyComponent () {
  const user = useUser()

  if (!user.permissions.includes('my_custom_permission')) {
    return null
  }

  return <div>Protected content</div>
}
```
