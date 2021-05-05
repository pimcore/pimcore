# Installers

Besides being enabled, bundles may need to execute installation tasks in order to be fully functional. This may concern
tasks like

* creating database tables
* creating or updating class definitions
* importing translations
* updating database tables or definitions after an update to a newer version
* ...

To give bundles full control over their install routines, Pimcore only defines a basic installer interface which must be
implemented by your installer. The methods implemented by your installer drive the extension manager UI and are called when
an action is triggered from the extension manager or from commands like `pimcore:bundle:install`. The basic installer
interface can be found in [InstallerInterface](https://github.com/pimcore/pimcore/blob/master/lib/Extension/Bundle/Installer/InstallerInterface.php) which
is implemented in [AbstractInstaller](https://github.com/pimcore/pimcore/blob/master/lib/Extension/Bundle/Installer/AbstractInstaller.php)
which you can use as starting point.

A pimcore bundle is expected to return an installer instance in `getInstaller()`. This method can also return `null` if you
don't need any installation functionality. In this case, actions which would be handled by an installer will not be available
in the extension manager (e.g. the install button is not shown).

It's recommended to define the installer as service and to fetch it from the container from your bundle class on demand.  
As example:

```yml
services:
    App\Installer:
        public: true
```

```php
<?php

namespace App;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class App extends AbstractPimcoreBundle
{
    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }
}
```

## Migrations

A common tasks in evolving bundles is to update an already existing/installed data structure to a newer version while also
supporting fresh installs of your bundle. To be able to apply versioned changes (migrations), Pimcore integrates the
[Doctrine Migrations Bundle](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)  which
provides a powerful migration framework.
For details how to work with migrations, please have a look at the [Doctrine Migrations Bundle documentation](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html).

### Pimcore Specifics

Pimcore added an additional option (`--prefix=`) to the migration commands of Doctrine, to be able to filter the migration versions
for a specific namespace. This gives you the possibility to control which migrations should be executed or not.
A typical use case for that would be to just run the Pimcore core migrations or just the migrations for a specific bundle.

To make sure, the migration command only executes migrations from installed Pimcore bundles, it is recommended to extend
the bundle migrations from `Pimcore\Migrations\BundleAwareMigration` and implement the `getBundleName` method.
This abstract class checks if the given bundle is installed and skips the migration if necessary.  


#### Console Examples

```bash
# only run migrations for the Pimcore core
./bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle

# list migrations for the CMF bundle
./bin/console doctrine:migrations:list --prefix=CustomerManagementFrameworkBundle\\Migrations

# run all migrations
./bin/console doctrine:migrations:migrate 
```  

#### Config Examples (`config.yml`)
```yml
doctrine_migrations:
    migrations_paths:
        'Pimcore\Bundle\DataHubBundle\Migrations': '@PimcoreDataHubBundle/Migrations'
        'CustomerManagementFrameworkBundle\Migrations': '@PimcoreCustomerManagementFrameworkBundle/Migrations'
```


## SettingsStore Installer

The `SettingsStoreAwareInstaller` adds the following functionality to the
default `AbstractInstaller`:

- Manage installation state with [Settings Store](../../../19_Development_Tools_and_Details/42_Settings_Store.md)
  (instead of checking executed migrations).
- Optionally mark certain migrations as migrated during install.
- Reset migration state of migrations (if there are any) during un-install.


### Implementation

For using the SettingsStore Installer extend from the `SettingsStoreAwareInstaller` and implement standard `install`
and `uninstall` methods. At the end of these methods either call the corresponding parent method or call
`$this->markInstalled()` / `$this->markUninstalled()` to make sure SettingsStore is updated properly.

If during install migrations upto a certain migration should be marked as migrated during install without actually executing
them, then also implement the `getLastMigrationVersionClassName` method that returns the fully qualified class name of the
last migration that should be marked as migrated.
This is useful, when install routine already does all the necessary things that also would be done by the migrations.

```php 
<?php
namespace Pimcore\Bundle\DummyBundle;

use Pimcore\Bundle\DummyBundle\Migrations\Version20210304111225;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{

    public function getLastMigrationVersionClassName(): ?string
    {
        // return fully qualified classname of last migration that should be marked as migrated during install
        return Version20210304111225::class;
    }

    public function install()
    {
        //do your install stuff   

        $this->markInstalled();
        //or call parent::install();     
    }

    public function uninstall()
    {
        //do your uninstall stuff

        $this->markUninstalled();
        //or call parent::uninstall();   
    }
}
```

```yml 
    Pimcore\Bundle\DummyBundle\Installer:
        public: true
        arguments:
            $bundle: "@=service('kernel').getBundle('PimcoreDummyBundle')"
```

### Installation
During installation of the bundle following things will happen:
- All statements of the `install` method are executed.
- If implemented correctly, the bundle is marked as installed in the SettingsStore.
- If configured, all defined migrations are marked as migrated (without actually executing them).

### Uninstallation
During uninstallation of the bundle following things will happen:
- All statements of the `uninstall` method are executed.
- If implemented correctly, the bundle is marked as uninstalled in the SettingsStore.
- Execution state of all bundle migrations that were already migrated will be reset (without actually executing them).


### Migrations
Working with migrations is the same as described in the Migration section above.

---

For further details please see

* [Migrations](../../../19_Development_Tools_and_Details/37_Migrations.md)
* [Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html)
* [Doctrine Migrations Bundle](http://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
