---
title: Modifying Permissions on Object Data
description: Dynamically adjust element permissions based on object data using GenericDataIndex events.
---

# Modifying Permissions Based on Object Data

The GenericDataIndex `PermissionEvent` fires when permissions are resolved and lets you modify them
before they reach Pimcore Studio. Use this to restrict editing based on object data rather than tree
structure.

**Example scenario:** A PIM system aggregates products from multiple ERP systems into one shared
object tree. All editors see every product, but editing permissions depend on the product's origin
ERP system. Since products move around in the tree, folder-based permissions are unreliable.

The `PermissionEvent` solves this by letting you modify permissions per object at access time. The event provides:

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

With Symfony's autowiring and autoconfiguration enabled (the default), the subscriber is automatically registered.
No manual service definition needed.

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
