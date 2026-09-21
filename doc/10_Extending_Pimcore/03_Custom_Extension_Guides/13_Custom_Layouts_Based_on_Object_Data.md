---
title: Custom Layouts Based on Object Data
description: Dynamically select custom layouts based on object data by decorating the Studio Backend LayoutService.
---

# Showing Custom Layouts Based on Object Data

Show different [Custom Layouts](../../03_Objects/01_Object_Classes/03_Custom_Layouts.md)
depending on the object's data, for example showing only the relevant fields for a given
hierarchy level in a product structure.

**Example scenario:** Products use data inheritance with three hierarchy levels (article,
color variant, size variant). Each level maintains different attributes. Editors should only
see the fields relevant to their hierarchy level.

**Approach:**

1. Create a [Custom Layout](../../03_Objects/01_Object_Classes/03_Custom_Layouts.md) for each hierarchy level.
2. Decorate the Studio Backend `LayoutServiceInterface` to dynamically select a custom layout
   based on object data when the default layout is requested.

## How Layout Resolution Works in Studio

Studio Backend resolves layouts through the `LayoutServiceInterface`. When the Studio UI opens a data object,
it makes two API calls:

1. `GET /api/class/custom-layout/editor/collection/{objectId}` — fetches the list of available layouts
2. `GET /api/data-objects/{id}/layout?layoutId=0` — fetches the actual layout definition

The UI defaults to `layoutId=0` (the main layout) unless a workflow or the `default` flag on a custom layout
overrides this. By decorating the `LayoutServiceInterface`, you can intercept this default request and
return a different layout based on the object's data.

## Service Decorator

The decorator wraps the original `LayoutServiceInterface`, checks the object's data when the default layout
(`layoutId=0`) or no layout is requested, and forwards a custom layout ID to the original service when
the criteria match. When the user explicitly picks a different layout from the layout switcher, the decorator
passes it through unchanged.

```php
<?php

declare(strict_types=1);

namespace App\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\LayoutServiceInterface;
use Pimcore\Model\DataObject\Car;

final readonly class CustomLayoutService implements LayoutServiceInterface
{
    private const CAR_TODO_LAYOUT_ID = 'CarTodo';

    public function __construct(
        private LayoutServiceInterface $inner,
    ) {
    }

    public function getDataObjectLayout(int $id, ?string $layoutId = null): Layout
    {
        if ($layoutId === null || $layoutId === '0') {
            $resolvedId = $this->resolveLayoutId($id);
            if ($resolvedId !== null) {
                $layoutId = $resolvedId;
            }
        }

        return $this->inner->getDataObjectLayout($id, $layoutId);
    }

    public function getClassLayout(string $classId): Layout
    {
        return $this->inner->getClassLayout($classId);
    }

    private function resolveLayoutId(int $id): ?string
    {
        $car = Car::getById($id);

        if ($car === null) {
            return null;
        }

        if ($car->getObjectType() === 'actual-car') {
            return self::CAR_TODO_LAYOUT_ID;
        }

        return null;
    }
}
```

## Service Registration

Register the decorator in `config/services.yaml`:

```yaml
services:
    App\DataObject\Service\CustomLayoutService:
        decorates: Pimcore\Bundle\StudioBackendBundle\DataObject\Service\LayoutServiceInterface
        arguments:
            $inner: '@.inner'
```

The `decorates` key wraps the original service. The `$inner` argument gives you access to the original
implementation, including its workflow integration and permission checks.

## How It Works

- When a user opens a Car with `objectType = "actual-car"`, the decorator intercepts the default layout
  request (`layoutId=0`) and returns the `CarTodo` custom layout instead
- When a Car has any other `objectType`, the decorator returns `null` and the original service follows its
  normal resolution chain (workflow layout → default class layout)
- When the user explicitly selects a different layout from the layout switcher (e.g. `layoutId=CP`),
  the decorator passes it through unchanged; only `null` and `'0'` are intercepted
- Non-Car objects are unaffected. `Car::getById()` returns `null` and the original flow takes over
