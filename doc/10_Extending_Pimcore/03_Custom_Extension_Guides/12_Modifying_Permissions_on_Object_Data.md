# Modifying Permissions Based on Object Data

The GenericDataIndex `PermissionEvent` can be used to modify element permissions based on data stored inside the
object itself. It fires when permissions are resolved and allows you to modify them before they reach Studio.

**Imagine following use case:**
Your PIM system aggregates different sources (e.g. multiple ERP systems from different sub companies) of products and merges
them to one single product hierarchy tree in order to have one single tree of products.
So all editors can see all products in one place and get a good overview of all available products, which is great.

When it comes to editing though, not all editors should be able to edit all products. The editing permissions for products
should be based on the ERP system they originate from.
Since the products are merged together into one tree structure, setting up such a permission structure might become tricky,
especially when products are moved around in the object tree.

**Solution**

Use the `PermissionEvent` from the GenericDataIndex bundle to modify user permissions on the fly based on object data
when the object is accessed. The event provides:

- **`getElement()`** — returns a `DataObjectSearchResultItem` (provides `getClassName()`, `getId()`, `getSearchIndexData()`)
- **`getPermissions()`** — returns a mutable `DataObjectPermissions` object with setters like `setSave()`, `setPublish()`, `setDelete()`, etc.

> For full details on the PermissionEvent and workspace permissions, see the
> [GenericDataIndex Permissions & Workspaces documentation](https://github.com/pimcore/generic-data-index-bundle/blob/2025.x/doc/04_Searching_For_Data_In_Index/08_Permissions_Workspaces/README.md).

Create an [Event Subscriber](../01_Events/README.md) that subscribes to the `PermissionEvent`:

```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\GenericDataIndexBundle\Event\DataObject\PermissionEvent;
use Pimcore\Model\DataObject\Product;
use Pimcore\Security\User\UserLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DataObjectPermissionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserLoader $userLoader,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PermissionEvent::class => 'checkPermissions',
        ];
    }

    public function checkPermissions(PermissionEvent $event): void
    {
        $element = $event->getElement();

        if ($element->getClassName() !== 'Product') {
            return;
        }

        $product = Product::getById($element->getId());

        if ($product === null) {
            return;
        }

        // Get product origin (e.g. which ERP system it comes from)
        $origin = $product->getOrigin() ?? 'unknown';

        // Check if the current user has permission for this origin
        $user = $this->userLoader->getUser();
        if (!$user || !$user->isAllowed("editing_origin_$origin")) {
            $permissions = $event->getPermissions();
            $permissions->setSave(false);
            $permissions->setPublish(false);
            $permissions->setUnpublish(false);
            $permissions->setDelete(false);
            $permissions->setCreate(false);
            $permissions->setRename(false);
        }
    }
}
```

With Symfony's autowiring and autoconfiguration enabled (the default), the subscriber is automatically registered —
no manual service definition needed.

## Available Permission Setters

The `DataObjectPermissions` class (extending `BasePermissions`) provides these setters:

| Method | Description |
|--------|-------------|
| `setSave(bool)` | Allow/deny saving |
| `setPublish(bool)` | Allow/deny publishing |
| `setUnpublish(bool)` | Allow/deny unpublishing |
| `setDelete(bool)` | Allow/deny deletion |
| `setRename(bool)` | Allow/deny renaming |
| `setView(bool)` | Allow/deny viewing |
| `setList(bool)` | Allow/deny listing |
| `setCreate(bool)` | Allow/deny creating children |
| `setSettings(bool)` | Allow/deny editing settings |
| `setVersions(bool)` | Allow/deny version management |
| `setProperties(bool)` | Allow/deny property editing |
| `setLocalizedEdit(?string)` | Restrict editable localized fields |
| `setLocalizedView(?string)` | Restrict viewable localized fields |

## Using Search Index Data Instead of Loading the Object

For better performance, you can read field values directly from the search index data instead of loading the
full Pimcore object. This avoids a database query per permission check:

```php
public function checkPermissions(PermissionEvent $event): void
{
    $element = $event->getElement();

    if ($element->getClassName() !== 'Product') {
        return;
    }

    $indexData = $element->getSearchIndexData();
    $origin = $indexData['origin'] ?? 'unknown';

    $user = $this->userLoader->getUser();
    if (!$user || !$user->isAllowed("editing_origin_$origin")) {
        $permissions = $event->getPermissions();
        $permissions->setSave(false);
        $permissions->setPublish(false);
        $permissions->setUnpublish(false);
    }
}
```

> **Note:** The structure of `getSearchIndexData()` depends on how fields are indexed. Check your index mapping
> to confirm the field name and format.
