# Configuration

## Environment-Specific Configurations
Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 

Pimcore is relying on Symfony's environments, with some extras, however all the essential 
information can be found in the [Symfony Docs](https://symfony.com/doc/5.2/configuration.html#configuration-environments)

In addition to Symfony configurations, Pimcore also supports environment specific configs for: 

* <https://github.com/pimcore/demo/tree/10.x/config/pimcore> 
* <https://github.com/pimcore/demo/tree/10.x/var/config>

The environment specific config file has priority over the default config, so if your 
current environment is `dev` Pimcore first checks if e.g. `var/config/image-thumbnails_dev.php`
exists, if not the default config `var/config/image-thumbnails.php` is used. 

## Configuration Storage Locations & Fallbacks
For certain configurations which can be edited in the user interface, 
Pimcore provides the possibility to configure them using various storage types. 
Available storages are (in priority order): 
- Symfony Config (YAML, needs container rebuild)
- Pimcore [`SettingsStore`](../19_Development_Tools_and_Details/42_Settings_Store.md)
- Pimcore Legacy PHP Array Config (deprecated)

This feature is currently supported by the following configurations: 
- Custom reports
- Image thumbnails 
- Video thumbnails

You can change the write target individually for each type by using environment variables.
The following options are available: 
- `symfony-config` - write configs as Symfony Config as YAML files to `/var/config/.../example.yaml`
- `settings-store` - write configs to the `SettingsStore`
- `disabled` - do not allow to edit/write configs at all

Available environment variables are: 
```bash
PIMCORE_WRITE_TARGET_IMAGE_THUMBNAILS=settings-store
PIMCORE_WRITE_TARGET_CUSTOM_REPORTS=settings-store
PIMCORE_WRITE_TARGET_VIDEO_THUMBNAILS=settings-store
```
