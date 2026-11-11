---
title: UUID Support
description: Assign and retrieve UUIDs for Pimcore elements.
---

# UUID Support

:::note
This feature requires the UUID bundle. Ensure `\Pimcore\Bundle\UuidBundle\PimcoreUuidBundle::class`
is in your `config/bundles.php` and the bundle is installed and enabled.
:::

Pimcore provides a toolkit for UUID support. To activate it, set an instance identifier
in `config.yaml`:

```yaml
pimcore_uuid:
  instance_identifier: 'your_unique_instance_identifier'
```

Once set, Pimcore automatically creates a UUID for each new document, asset, class, and object.
Access UUIDs with `Tool\UUID`:

```php
use Pimcore\Bundle\UuidBundle\Model\Tool;

//get UUID for given element (document, asset, class, object)
$uuid = Tool\UUID::getByItem($document);

//get element for given UUID
$document = Tool\UUID::getByUuid($uuid);

//create and save uuid for given element
$uuid = Tool\UUID::create($document);
```
