---
title: Settings Store
description: Key-value store API for persisting bundle and application settings.
---

# Settings Store

The Settings Store is a key-value store that persists settings into the Pimcore database via API.
No user interface is available. Unlike `TmpStore`, settings have no expiry date and are not cleaned up.

Use cases:
- Track whether a bundle is installed
- Store runtime settings of a bundle

Settings support namespacing/grouping via a `scope` attribute and accept the following scalar data types
(use `SettingsStore::TYPE_*` constants):
- `SettingsStore::TYPE_STRING` (`string`)
- `SettingsStore::TYPE_BOOLEAN` (`bool`)
- `SettingsStore::TYPE_INTEGER` (`int`)
- `SettingsStore::TYPE_FLOAT` (`float`)

Use the `scope` attribute when storing settings for a bundle (e.g. the bundle name).
Omit it when using the Settings Store for your application.

### Sample Usage

With scope (recommended for bundles):

```php

// store or update setting (id, data, type, scope)
SettingsStore::set('my-setting-id', 'this is some setting value', SettingsStore::TYPE_STRING, 'bundle-settings-1');

// load setting by id (id, scope)
$setting = SettingsStore::get('my-setting-id', 'bundle-settings-1');

// load all settings ids for specific scope
$ids = SettingsStore::getIdsByScope('bundle-settings-1');

// delete setting (id, scope)
SettingsStore::delete('my-setting-id', 'bundle-settings-1');

```

Without scope:

```php

// store or update setting (id, data, type)
SettingsStore::set('my-setting-id', 'this is some setting value', 'string');

// load setting by id
$setting = SettingsStore::get('my-setting-id');

// load all settings ids for specific scope
$ids = SettingsStore::getIdsByScope('bundle-settings-1');

// delete setting
SettingsStore::delete('my-setting-id');

```
