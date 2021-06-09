# Settings Store

The settings store is a simple key value store and allows to persist any kind of settings into the 
Pimcore database via API. There is no user interface for the Settings Store available. Compared to the
`TmpStore` the settings do not have an expiry date and will not be cleaned up. 

Sample use cases for settings store are:
- Persist if a bundle is installed.
- Runtime settings of a bundle. 
- ... 

The stored settings can be namespaced/grouped with a `scope` attribute and can be of following scalar data 
types: 
- `string`
- `bool`
- `int`
- `float`

We highly recommend to use the `scope` attribute when using the settings store for a bundle (e.g. the bundles name), 
while you can omit it when using the settings store for your app. 

### Sample Usage

```php 

// store or update setting
SettingsStore::set('my-setting-id', 'this is some setting value', 'bundle-settings-1', 'string');

// load setting by id
$setting = SettingsStore::get('my-setting-id');

// load all settings ids for specific scope
$ids = SettingsStore::getIdsByScope('bundle-settings-1');

// delete setting
SettingsStore::delete('my-setting-id');

```