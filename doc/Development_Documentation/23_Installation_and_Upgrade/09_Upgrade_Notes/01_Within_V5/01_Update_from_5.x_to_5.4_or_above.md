# Step by Step Guide for Pimcore Updates from 5.x to 5.4 or above

If you're already on Pimcore 5 and you'd like to update to >= 5.4, we recommend to not further use the existing 
updater (cli or web) even if it does offer the update to 5.4. The following guide brings you much faster onto 
the newer version and is less error prone.  

> **IMPORTANT NOTICE!**  
If your're running PHP 7.1 please update to PHP 7.2 prior the upgrade, this is due to a PHP bug (https://bugs.php.net/bug.php?id=74586)

## 1. Check your current build number ...
... with the following command
```bash
cat pimcore/lib/Pimcore/Version.php | grep revision
```
and store it for future reference. 

## 2. Prepare the manual migrations script
Even if we don't use the legacy updater, we still need to perform the necessary migration scripts. 
We've prepared a simple PHP script that does all the necessary steps for us.
Just install the script in your project-root with the following command: 
```bash
wget https://gist.githubusercontent.com/brusch/c3e572947a7a7e8523e18e9787cf88c3/raw/pimcore-migrations-40-to-54.php -O manual-migration.php
```

## 3. Cleanup your `composer.json`

Remove all Pimcore composer dependencies from your project's `composer.json`: 
```
composer remove --no-update symfony/symfony amnuts/opcache-gui cache/tag-interop colinmollenhour/credis composer/ca-bundle debril/rss-atom-bundle \
defuse/php-encryption doctrine/annotations doctrine/cache doctrine/collections doctrine/common doctrine/dbal doctrine/doctrine-bundle \
doctrine/doctrine-migrations-bundle doctrine/instantiator egulias/email-validator endroid/qr-code geoip2/geoip2 google/apiclient \
guzzlehttp/guzzle hybridauth/hybridauth lcobucci/jwt league/csv linfo/linfo mjaschen/phpgeo monolog/monolog mpratt/embera myclabs/deep-copy \
myclabs/php-enum neitanod/forceutf8 nesbot/carbon ocramius/package-versions ocramius/proxy-manager oyejorge/less.php pear/net_url2 \
phive/twig-extensions-deferred pimcore/core-version piwik/device-detector presta/sitemap-bundle ramsey/uuid sabre/dav sensio/distribution-bundle \
sensio/framework-extra-bundle sensio/generator-bundle sensiolabs/ansi-to-html symfony-cmf/routing-bundle symfony/monolog-bundle symfony/polyfill-apcu \
symfony/swiftmailer-bundle tijsverkoyen/css-to-inline-styles twig/extensions twig/twig umpirsky/country-list vrana/adminer vrana/jush \
wa72/htmlpagedom zendframework/zend-code zendframework/zend-paginator zendframework/zend-servicemanager scheb/two-factor-bundle 
```

Remove the `name` and `type` property out of your `composer.json`: 
```json
"name": "pimcore/pimcore",
"type": "project",
```

Replace the `scripts` and `autoload` sections in your `composer.json` with the following: 
```json
  "autoload": {
    "psr-4": {
      "": ["src/"],
      "Pimcore\\Model\\DataObject\\": "var/classes/DataObject",
      "Pimcore\\Model\\Object\\": "var/classes/Object",
      "Website\\": "legacy/website/lib"
    },
    "classmap": [
      "app/AppKernel.php"
    ]
  },
  "scripts": {
    "post-create-project-cmd": "Pimcore\\Composer::postCreateProject",
    "post-install-cmd": [
      "Pimcore\\Composer::postInstall",
      "@symfony-scripts"
    ],
    "post-update-cmd": [
      "Pimcore\\Composer::postUpdate",
      "@symfony-scripts",
      "Pimcore\\Composer::executeMigrationsUp"
    ],
    "pre-package-update": [
      "Pimcore\\Composer::prePackageUpdate"
    ],
    "symfony-scripts": [
      "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::clearCache",
      "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets",
      "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installRequirementsFile",
      "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::prepareDeploymentTarget"
    ]
  },
```

## 4. Install Pimcore as Composer dependency 
```
rm composer.lock
rm -rf vendor
COMPOSER_MEMORY_LIMIT=-1 composer require pimcore/pimcore:5.4.*
```

If this doesn't help, try to remove the remaining dependencies until you've found the package that causes the issue. 
Don't fully trust the error message of Composer, that can be completely misleading! 

## 5. Cleanup project files
```
rm -r pimcore/
rm -r web/pimcore/
```

If you have scripts that rely (include or require) on Pimcore's startup scripts (`startup.php` and `startup_cli.php`) 
which used to be located under `/pimcore/config/`, you can keep that folder in your project for compatibility reasons. 
This won't have any side-effects, since they are just calling functions from within the Pimcore library, so you can keep
them as long as it is necessary.  

## 6. Pimcore Static Resources Path Change
Since the Pimcore admin user interface is now also a Symfony bundle, the path to static resources has changed from 
`/pimcore/static6/` to `/bundles/pimcoreadmin/`. If you're using Pimcore static resources somewhere in your application 
you'd have to change the path accordingly or you can use the following RewriteRule in your `.htaccess`: 
```
RewriteRule ^pimcore/static6/(.*) /bundles/pimcoreadmin/$1 [PT,L]
```

## 7. Perform pre-5.4 migrations manually
Now we're executing the script we have prepared already earlier in step 2. 
The script accepts one argument, which is the build number we were on, before the update (see step 1). 
In the following example we were on build 100, replace `100` with your build number! 

```
php manual-migration.php 100
```

Additionally we have to update to files in the project manually, use the following commands: 
```
wget https://raw.githubusercontent.com/pimcore/skeleton/master/bin/console -O bin/console
chmod 0755 bin/console
wget https://raw.githubusercontent.com/pimcore/skeleton/master/web/app.php -O web/app.php
wget https://raw.githubusercontent.com/pimcore/demo-basic/master/app/AppKernel.php -O app/AppKernel.php
```

## 8. Execute initial and latest migrations
execute these commands to initialize the composer migration, otherwise your composer update won't execute any migrations in the future:
```
bin/console pimcore:migrations:execute -s pimcore_core 20180724144005
bin/console pimcore:migrations:migrate -s pimcore_core
```

## 9. Done 
You should be done now, Pimcore should boot up and behave as normal. 
