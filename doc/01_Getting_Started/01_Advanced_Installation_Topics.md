# Advanced Installation Topics

To fully automate the installation process, options can be passed in the CLI as parameters, rather than adding them interactively. 

The `--no-interaction` flag will prevent any interactive prompts:

```bash
./vendor/bin/pimcore-install --admin-username=admin --admin-password=admin \
  --mysql-username=username --mysql-password=password --mysql-database=pimcore \
  --mysql-host-socket=127.0.0.1 --mysql-port=3306 \
  --no-interaction
```

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

- [PimcoreApplicationLoggerBundle](../18_Tools_and_Features/17_Application_Logger.md)
- [PimcoreCustomReportsBundle](../18_Tools_and_Features/29_Custom_Reports.md)
- [PimcoreGlossaryBundle](../18_Tools_and_Features/21_Glossary.md)
- PimcoreSeoBundle (for SEO-related topics: [Robots.txt](../18_Tools_and_Features/38_Robots.txt.md), [Sitemaps](../18_Tools_and_Features/39_Sitemaps.md) and [Redirects](../02_MVC/04_Routing_and_URLs/04_Redirects.md))
- PimcoreSimpleBackendSearchBundle (for default search functionality in Backend UI interface)
- [PimcoreStaticRoutesBundle](../02_MVC/04_Routing_and_URLs/02_Custom_Routes.md)
- [PimcoreTinymceBundle](https://github.com/pimcore/pimcore/blob/11.x/bundles/TinymceBundle/README.md) (for default WYSIWYG editor)
- [PimcoreUuidBundle](../19_Development_Tools_and_Details/19_UUID_Support.md)
- PimcoreWordExportBundle (for import/export functionality for translations in Word format)
- PimcoreXliffBundle (for import/export functionality for translations in Xliff format)


### Preconfiguring the Installer

You can preconfigure the values used by the installer by adding a config file which sets values for the database
credentials. This is especially useful when installing Pimcore on platforms where credentials are available via env vars
instead of having direct access to them. To preconfigure the installer, add a config file in `config/installer.yaml` 
(note: the file can be of any format supported by Symfony's config, so you could also use xml or php as the format), then configure the `pimcore_installer` tree:

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
