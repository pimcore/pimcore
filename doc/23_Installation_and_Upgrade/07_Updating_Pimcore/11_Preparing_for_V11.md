# Preparing Pimcore for Version 11

## Upgrade to Pimcore 10.6
- Upgrade to version 10.6.x, if you are using a lower version.

## Migrate PHP Templates to Twig

With Pimcore 11, it is required to update your PHP templates to Twig.

:::caution

Be aware that Pimcore 11 does not support `installing pimcore/php-templating-engine-bundle` anymore. The migration to Twig is then required even for enterprise customers.

:::

You can use a [RegEx](https://gist.github.com/putzflorian/219f582377b20d64d97ea9d8751dbb89) to replace strings in your template files. For example, rewrite `{% extends ':Layout:default.html.twig' %}` to `{% extends 'Layout/default.html.twig' %}`.

:::tip 

Better replace the strings manually with your IDE instead of as a batch process.

:::

## Code Changes
- [Type hints] Check and add **return type hints** for classes extending Pimcore classes or implementing interfaces provided by Pimcore, based on the source phpdoc or comments on the methods.
  The return types will be added to Pimcore classes, so you _**must**_ add return types to your classes extending Pimcore.
  You could use the patch-type-declarations tool, provided by symfony, to check for affected methods. For details please have a look [here](https://symfony.com/doc/5.4/setup/upgrade_major.html#4-update-your-code-to-work-with-the-new-version).

- [Javascript] Replace plugins with [event listener](../../20_Extending_Pimcore/13_Bundle_Developers_Guide/06_Event_Listener_UI.md) as follows:
    ```javascript
    pimcore.registerNS("pimcore.plugin.MyTestBundle");

    pimcore.plugin.MyTestBundle = Class.create(pimcore.plugin.admin, {
        getClassName: function () {
            return "pimcore.plugin.MyTestBundle";
        },
    
        initialize: function () {
            pimcore.plugin.broker.registerPlugin(this);
        },
    
        preSaveObject: function (object, type) {
            var userAnswer = confirm("Are you sure you want to save " + object.data.general.className + "?");
            if (!userAnswer) {
                throw new pimcore.error.ActionCancelledException('Cancelled by user');
            }
        }
    });
    
    var MyTestBundlePlugin = new pimcore.plugin.MyTestBundle();
    ```
    
    ```javascript
    document.addEventListener(pimcore.events.preSaveObject, (e) => {
        let userAnswer = confirm(`Are you sure you want to save ${e.detail.object.data.general.className}?`);
        if (!userAnswer) {
           e.preventDefault();
           e.stopPropagation();
           pimcore.helpers.showNotification(t("Info"), t("saving_failed") + ' ' + 'placeholder', 'info');
        }
    });
    ```
- [Javascript] Replace deprecated JS functions:
   - Use t() instead of ts() for translations.
   - Stop using `pimcore.helpers.addCsrfTokenToUrl`
 
- [Deprecations] Fix deprecations defined in the [upgrade notes](../09_Upgrade_Notes/README.md), which is to be removed in Pimcore 11.
  Tip: you can search for deprecations in Symfony Profiler(Debug mode) or can run linux command `tail -f var/log/dev.log | grep 'User Deprecated'` for checking deprecations on runtime.

- [Extensions] Stop using `var/config/extensions.php` for registering bundles, use `config/bundle.php` instead.

- Don't use deprecated `Pimcore\Db\ConnectionInterface` interface, `Pimcore\Db\Connection` class and `Pimcore\Db\PimcoreExtensionsTrait` trait
  Use `Doctrine\DBAL\Driver\Connection` interface and `Doctrine\DBAL\Connection` class instead.
  Some methods must be replaced:
  - Use `executeQuery()` instead of `query()`
  - Use `executeStatement()` instead of `executeUpdate()`, `deleteWhere()`, `updateWhere()`
  - Use `fetchAssociative()` instead of `fetchRow()`
  - Use `fetchFirstColumn()` instead of `fetchCol()`
  - Use `Pimcore\Db\Helper::fetchPairs()` instead of `fetchPairs()`
  - Use `Pimcore\Db\Helper::upsert()` instead of `insertOrUpdate()`
  - Use `Pimcore\Db\Helper::quoteInto()` instead of `quoteInto()`
  - Use `quoteIdentifier()` instead of `quoteColumnAs()`
  - Don't use `quoteTableAs()`
  - Don't use `limit()`
  - Use `Pimcore\Db\Helper::queryIgnoreError()` instead of `queryIgnoreError()`
  - Use `Pimcore\Db\Helper::selectAndDeleteWhere()` instead of `selectAndDeleteWhere()`
  - Use `Pimcore\Db\Helper::escapeLike()` instead of `escapeLike()`

- [Ecommerce] Switch to ElasticSearch8 implementations in case you are using elasticsearch indices. 

- [Symfony]
  - Require `symfony/dotenv` package in your project to keep using `.env` files and stop using `PIMCORE_SKIP_DOTENV_FILE` env var as by default it is skipped. You  still could use environment specific file like `.env.test` or `.env.prod` for environment specific environment variables. 
    ```bash
    composer require --no-update symfony/dotenv
    ```
- [Deprecations] Constant `PIMCORE_PHP_ERROR_LOG` is deprecated and will be removed in Pimcore 11

## Migrations
Make sure that migrations are executed.
How to handle them highly depends on your deployment process.
You can manually call `bin/console doctrine:migrations:migrate` at any time or add it in your deployment pipeline.

If you are sure you can run all available migrations after `composer update`, including bundles and your app-specific migrations, just include the following part in your `composer.json` file:
```json
"post-update-cmd": [
    "./bin/console doctrine:migrations:migrate"
]
```

## Configuration Adaptions
- [Security] Enable New Security Authenticator and adapt your `security.yaml` file as per changes [here](https://github.com/pimcore/demo/blob/11.x/config/packages/security.yaml):
    ```
    security:
        enable_authenticator_manager: true
    ```
    Points to consider when moving to new Authenticator:
  - New authentication system works with password hasher factory instead of encoder factory.
  - BruteforceProtectionHandler will be replaced with Login Throttling.
  - Custom Guard Authenticator will be replaced with Http\Authenticator.
  
- [Config Environment] Replace deprecated setting write targets and storage directory in the .env file with symfony config
    ```bash
    PIMCORE_WRITE_TARGET_IMAGE_THUMBNAILS=symfony-config
    PIMCORE_WRITE_TARGET_CUSTOM_REPORTS=settings-store
  
    PIMCORE_CONFIG_STORAGE_DIR_IMAGE_THUMBNAILS=/var/www/html/var/config/image-thumbnails
    ```
  For example, see the [Demo Configuration](https://github.com/pimcore/demo/blob/7add4ddd30be82687ba5c4bbef8048e794e58923/config/config.yaml#L28).
    ```yaml
    pimcore:
      config_location:
        image_thumbnails:
          write_target:
            type: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/image-thumbnails'
        document_types:
          write_target:
            type: 'settings-store'
        
        # other available write targets are the following
        # video_thumbnails:
        # web_to_print:
        # predefined_properties:
        # staticroutes:
        # perspectives:
        # custom_views:
        # object_custom_layouts:
        # predefined_asset_metadata:
    ```
    
    You might also adapt the `config_location` from other extensions, like Datahub.

### Migrate o_ prefix properties in the stored data
As `o_` prefix will be removed from data objects system properties in v11. It is recommended to migrate the stored data to use new properties (without o_ prefix).
Please adapt and use these [scripts](https://gist.github.com/dvesh3/50a1a99fd337d461e1652f2fd3b4d6cd) to migrate versions and recycle-bin data.

## Additional Things to Consider

- [Web2Print] Please keep in mind that the deprecated processor `HeadlessChrome` needs to be replaced with the new processor `Chrome` in Pimcore 11.
- [Config] `pimcore.assets.image.focal_point_detection` was removed
- [Composer] Please make sure to add the `pimcore/compatibility-bridge-v10` to your composer.json file:
    ```bash
    composer require --no-update pimcore/compatibility-bridge-v10
    ```
    This package provides backward compatibility layer for some Pimcore 10 classes.
- [Definition Files] Make sure your definition files in `var/classes` are up-to-date and all default values are set correctly by running following migration:
  ```bash
  bin/console doctrine:migration:exec 'Pimcore\Bundle\CoreBundle\Migrations\Version20230508121105'
  ```
