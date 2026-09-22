---
title: Overriding Models
description: Override Pimcore core model classes with custom implementations.
---

# Overriding Models

Override core model classes to add custom methods, modify behavior, or extend
functionality without editing vendor code. This works for all concrete
subclasses of the following base classes (but not for the base classes
themselves):

- `Pimcore\Model\Document`
- `Pimcore\Model\Document\Listing`
- `Pimcore\Model\DataObject\AbstractObject`
- `Pimcore\Model\DataObject\Listing`
- `Pimcore\Model\Asset`
- `Pimcore\Model\Asset\Listing`

For example, overriding `Pimcore\Model\DataObject\News\Listing` or
`Pimcore\Model\Asset\Image` is supported. Overriding the abstract base classes
directly (e.g. `Pimcore\Model\Asset`) is not possible because it would break
the class hierarchy for all subclasses.

## Configure an Override

Add a key/value mapping under `pimcore.models.class_overrides` in your
`config/config.yaml`. Your override class must extend the original class -
failing to do so will break the system.

```yaml
pimcore:
    models:
        class_overrides:
            'Pimcore\Model\DataObject\News': 'App\Model\DataObject\News'
            'Pimcore\Model\DataObject\News\Listing': 'App\Model\DataObject\News\Listing'
```

Your `App\Model\DataObject\News`:

```php
<?php

namespace App\Model\DataObject;

class News extends \Pimcore\Model\DataObject\News
{
    public function getMyCustomAttribute(): mixed
    {
        // ...
    }
}
```

:::warning
Clear all caches (Symfony + Data Cache) after configuring a class override:

```bash
bin/console cache:clear --no-warmup && bin/console pimcore:cache:clear
```
:::
