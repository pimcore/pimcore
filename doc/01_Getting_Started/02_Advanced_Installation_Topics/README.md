# Advanced Installation Topics

To fully automate the installation process, options can be passed in the CLI as parameters, rather than adding them interactively. 

##### For Docker installation:

```bash
docker compose exec php vendor/bin/pimcore-install --admin-username=admin --admin-password=admin \
  --mysql-username=username --mysql-password=password --mysql-database=pimcore \
  --mysql-host-socket=127.0.0.1 --mysql-port=3306 \
  --no-interaction
```

##### For webserver installation:

```bash
./vendor/bin/pimcore-install --admin-username=admin --admin-password=admin \
  --mysql-username=username --mysql-password=password --mysql-database=pimcore \
  --mysql-host-socket=127.0.0.1 --mysql-port=3306 \
  --no-interaction
```

:::info

The `--no-interaction` flag will prevent any interactive prompts.

:::

To avoid having to pass sensitive data (e.g. DB password) as command line option, you can also set each parameter as env
variable. See `./vendor/bin/pimcore-install` for details. Example:

```bash
$ PIMCORE_INSTALL_MYSQL_USERNAME=username PIMCORE_INSTALL_MYSQL_PASSWORD=password ./vendor/bin/pimcore-install \
  --admin-username=admin --admin-password=admin \
  --mysql-database=pimcore \
  --no-interaction
```

### Installing Bundles

The `--install-bundles` flag will install and enable the specified bundles.  
Attention: The bundles will be added to `config/bundles.php` automatically.

```bash
./vendor/bin/pimcore-install --admin-username=admin --admin-password=admin \
--mysql-username=username --mysql-password=password --mysql-database=pimcore \
--mysql-host-socket=127.0.0.1 --mysql-port=3306 \
--install-bundles=PimcoreApplicationLoggerBundle,PimcoreCustomReportsBundle \
--no-interaction
```

Available bundles for installation: 

- [PimcoreApplicationLoggerBundle](../../18_Tools_and_Features/17_Application_Logger.md)
- [PimcoreCustomReportsBundle](../../18_Tools_and_Features/29_Custom_Reports.md)
- [PimcoreGlossaryBundle](../../18_Tools_and_Features/21_Glossary.md)
- PimcoreSeoBundle (for SEO-related topics: [Robots.txt](../../18_Tools_and_Features/38_Robots.txt.md), [Sitemaps](../../18_Tools_and_Features/39_Sitemaps.md) and [Redirects](../../02_MVC/04_Routing_and_URLs/04_Redirects.md))
- PimcoreSimpleBackendSearchBundle (for default search functionality in Backend UI interface)
- [PimcoreStaticRoutesBundle](../../02_MVC/04_Routing_and_URLs/02_Custom_Routes.md)
- [PimcoreTinymceBundle](https://github.com/pimcore/pimcore/blob/11.x/bundles/TinymceBundle/README.md) (for default WYSIWYG editor)
- [PimcoreUuidBundle](../../19_Development_Tools_and_Details/19_UUID_Support.md)
- PimcoreWordExportBundle (for import/export functionality for translations in Word format)
- PimcoreXliffBundle (for import/export functionality for translations in Xliff format)

#### Adding or Removing Bundles / Bundle Recommendations
Before bundles are displayed in the installation process, the `BundleSetupEvent` is fired.
You can listen or subscribe to this event to add/remove bundles.
Note that a recommendation will only be added if the bundle is already in the bundles list.
For more info, you can take a look at the [Pimcore Skeleton](https://github.com/pimcore/skeleton) to see how the [Admin UI Classic Bundle](https://github.com/pimcore/admin-ui-classic-bundle) is installed.

```php
<?php

namespace App\EventSubscriber;

use Pimcore\Bundle\AdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\InstallBundle\Event\BundleSetupEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BundleSetupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InstallEvents::EVENT_BUNDLE_SETUP => [
                ['bundleSetup'],
            ],
        ];
    }

    public function bundleSetup(BundleSetupEvent $event): void
    {
        // add installable bundle and recommend it
        $event->addInstallableBundle('PimcoreAdminBundle', PimcoreAdminBundle::class, true);

        // add required bundle
        $event->addRequiredBundle('PimcoreAdminBundle', PimcoreAdminBundle::class);
    }
}
```

Make sure to register your listener/subscriber under `config/installer.yaml` as described in [Preconfiguring the Installer](#preconfiguring-the-installer).

```yaml
services:
    # default configuration for services in *this* file
    _defaults:
        # automatically injects dependencies in your services
        autowire: true
        # automatically registers your services as commands, event subscribers, etc.
        autoconfigure: true
        # this means you cannot fetch services directly from the container via $container->get()
        # if you need to do this, you can override this setting on individual services
        public: false

    # ---------------------------------------------------------
    # Event Subscribers
    # ---------------------------------------------------------
    App\EventSubscriber\BundleSetupSubscriber: ~

```

### Preconfiguring the Installer

You can preconfigure the values used by the installer by adding a config file which sets values for the database
credentials. This is especially useful when installing Pimcore on platforms where credentials are available via env vars
instead of having direct access to them. To preconfigure the installer, add a config file in `config/installer.yaml` 
(note: the file can be of any format supported by Symfony's config, so you could also use xml or php as the format), then configure the `pimcore_install` tree:

```yaml
# config/installer.yaml

pimcore_install:
    parameters:
        database_credentials:
            user:                 username
            password:             password
            dbname:               pimcore
            
            # env variables can be directly read with the %env() syntax
            # see https://symfony.com/blog/new-in-symfony-3-2-runtime-environment-variables
            host:                 "%env(DB_HOST)%"
            port:                 "%env(DB_PORT)%"
```

## Set a Timezone
Make sure to set the corresponding timezone in your configuration. 
It will be used for displaying date/time values in the admin backend.

```yaml
pimcore:
    general:
        timezone: Europe/Berlin
```

## Office document preview

The feature for displaying a [preview of documents](../../04_Assets/03_Working_with_Thumbnails/05_Document_Thumbnails.md) directly in Pimcore is optional. To use it, you must install either [Gotenberg](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#gotenberg) or [LibreOffice](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#libreoffice-pdftotext-inkscape-) according to your preference.
