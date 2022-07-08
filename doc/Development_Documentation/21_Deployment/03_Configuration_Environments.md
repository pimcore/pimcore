# Configuration

## Environment-Specific Configurations
Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 

Pimcore is relying on Symfony's environments, with some extras, however all the essential 
information can be found in the [Symfony Docs](https://symfony.com/doc/5.2/configuration.html#configuration-environments)

> Note: While Pimcore uses Symfony's DotEnv component to allow you to 
[configure environment variables in `.env` files](https://symfony.com/doc/5.4/configuration.html#configuring-environment-variables-in-env-files), 
sometimes (e.g. in *prod* environments) you may want to configure everything via real 
environment variables instead. In this case, you can disable loading of `.env` files 
by setting the `PIMCORE_SKIP_DOTENV_FILE` environment variable to a truthy value.

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
- Document types
- Image thumbnails 
- Video thumbnails
- Web2Print Settings
- Predefined properties
- Predefined asset metadata
- Static Routes
- Perspectives
- Custom views


You can change the write target individually for each type by using environment variables.
The following options are available: 
- `symfony-config` 
  - write configs as Symfony Config as YAML files to the configured storage directory
- `settings-store` 
  - write configs to the `SettingsStore`
- `disabled` 
  - do not allow to edit/write configs at all

Available environment variables are: 
```bash
PIMCORE_WRITE_TARGET_IMAGE_THUMBNAILS=settings-store
PIMCORE_WRITE_TARGET_CUSTOM_REPORTS=settings-store
PIMCORE_WRITE_TARGET_VIDEO_THUMBNAILS=settings-store
PIMCORE_WRITE_TARGET_DOCUMENT_TYPES=settings-store
PIMCORE_WRITE_TARGET_WEB_TO_PRINT=settings-store
PIMCORE_WRITE_TARGET_PREDEFINED_PROPERTIES=settings-store
PIMCORE_WRITE_TARGET_PREDEFINED_ASSET_METADATA=settings-store
PIMCORE_WRITE_TARGET_STATICROUTES=settings-store
PIMCORE_WRITE_TARGET_PERSPECTIVES=settings-store
PIMCORE_WRITE_TARGET_CUSTOM_VIEWS=settings-store
```
#### Storage directory for symfony Config files

The default storage directory for Symfony Config files is `/var/config/...`. However you can change
the directory to any other directory using the corresponding environment variable:

```bash
PIMCORE_CONFIG_STORAGE_DIR_IMAGE_THUMBNAILS=/var/www/html/var/config/image-thumbnails
PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_REPORTS=/var/www/html/var/config/custom-reports
PIMCORE_CONFIG_STORAGE_DIR_VIDEO_THUMBNAILS=/var/www/html/var/config/video-thumbnails
PIMCORE_CONFIG_STORAGE_DIR_DOCUMENT_TYPES=/var/www/html/var/config/document-types
PIMCORE_CONFIG_STORAGE_DIR_WEB_TO_PRINT=/var/www/html/var/config/web-to-print
PIMCORE_CONFIG_STORAGE_DIR_PREDEFINED_PROPERTIES=/var/www/html/var/config/predefined-properties
PIMCORE_CONFIG_STORAGE_DIR_PREDEFINED_ASSET_METADATA=/var/www/html/var/config/predefined-asset-metadata
PIMCORE_CONFIG_STORAGE_DIR_STATICROUTES=/var/www/html/var/config/staticroutes
PIMCORE_CONFIG_STORAGE_DIR_PERSPECTIVES=/var/www/html/var/config/perspectives
PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_VIEWS=/var/www/html/var/config/custom-views
```

#### Production environment with `symfony-config`
When using `symfony-config` write target, configs are written to Symfony Config files (`yaml`), which are only getting revalidated in debug mode. So if you're
changing configs in production you won't see any update, because these configs are read only.

If you'd like to allow changes in production, switch to the alternate `settings-store` config storage. 
You can do so by adding the following to your `.env` or just set the env variable accordingly, e.g.:
```
PIMCORE_WRITE_TARGET_CUSTOM_REPORTS=settings-store
```

#### Revalidate existing configuration on production
With `settings-store` target, one can update/change configurations in production environment, which in turn revalidates the generated files e.g. Image Thumbnails, Video thumbnails for subsequent requests.

This is not the case with `symfony-config` write target, as configurations are read-only and deployed from different environment. So we need to explicitly revalidate the generated files either through command or custom script. 

For example, to revalidate image or video thumbnails either run command `pimcore:thumbnails:clear` or call `Asset\Image\Thumbnail\Config::clearTempFiles()` after deploying changes on thumbnail configurations.
