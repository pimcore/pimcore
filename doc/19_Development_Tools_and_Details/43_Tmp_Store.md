# Tmp Store

The `TmpStore` is a simple key-value store that persists temporary data into the Pimcore database.
Stored entries can have an optional expiry date and will be cleaned up automatically during maintenance.

Compared to the [Settings Store](./42_Settings_Store.md), the `TmpStore` is intended for **transient** data
(e.g. intermediate processing results, queued job state) that is only needed for a limited time.

> **Note:** Objects and arrays stored in `TmpStore` are serialized via PHP's `serialize()` / `unserialize()`.
> Only a defined set of PHP classes is allowed during unserialization for security reasons.
> See [Extending Allowed Classes for Unserialization](#extending-allowed-classes-for-unserialization) below.

## API

```php
use Pimcore\Model\Tool\TmpStore;

// Store a value (creates or updates)
TmpStore::set('my-key', $data, 'my-tag', 3600); // last arg: lifetime in seconds

// Retrieve a stored entry
$entry = TmpStore::get('my-key');
if ($entry) {
    $value = $entry->getData();
    $tag    = $entry->getTag();
}

// Retrieve all IDs for a given tag
$ids = TmpStore::getIdsByTag('my-tag');

// Delete a single entry
TmpStore::delete('my-key');
```

### Method Reference

| Method                                   | Description                                                                     |
| ---------------------------------------- | ------------------------------------------------------------------------------- |
| `TmpStore::set(id, data, tag, lifetime)` | Create or update an entry. `lifetime` is in seconds (null = no expiry).         |
| `TmpStore::add(id, data, tag, lifetime)` | Alias for `set()`.                                                              |
| `TmpStore::get(id)`                      | Return the `TmpStore` model for the given ID, or `null` if not found / expired. |
| `TmpStore::getIdsByTag(tag)`             | Return all IDs associated with a tag.                                           |
| `TmpStore::delete(id)`                   | Delete a single entry by ID.                                                    |

## Extending Allowed Classes for Unserialization

When Pimcore reads a serialized object from the `tmp_store` table it passes an `allowed_classes` list to
PHP's `unserialize()` to prevent deserialization of unexpected classes (see [PHP docs](https://www.php.net/manual/en/function.unserialize.php)).

If your code stores custom objects in `TmpStore` you must register those classes through the Symfony
configuration, otherwise they will be rejected and the entry will be deleted automatically:

```yaml
# config/packages/pimcore.yaml
pimcore:
    tmp_store:
        unserialize_allowed_classes:
            - App\Model\MyTransferObject
            - Vendor\Bundle\SomeSerializableClass
```

The classes listed here are **merged** with Pimcore's built-in defaults, so you do not need to repeat
the core classes.
