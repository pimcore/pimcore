
# How to use an install profile without installing it

This example uses the profile `demo-cms`, so for other profiles you may change the commands below accordingly. 

## Modify your `constants.php`
```php
define("PIMCORE_APP_ROOT", realpath(__DIR__ . "/../install-profiles/demo-cms/app"));
define("PIMCORE_ASSET_DIRECTORY", realpath(__DIR__ . "/../install-profiles/demo-cms/web/var/assets"));
define('PIMCORE_APP_BUNDLE_CLASS_FILE', __DIR__ . "/../install-profiles/demo-cms/src/AppBundle/AppBundle.php");
```

## Create the following symlinks
```
ln -sr app/config/* install-profiles/demo-cms/app/config/
ln -sr install-profiles/demo-cms/app/config/parameters.example.yml install-profiles/demo-cms/app/config/parameters.yml
ln -sr app/*.php install-profiles/demo-cms/app/
ln -sr install-profiles/demo-cms/web/static/ web/static
```
