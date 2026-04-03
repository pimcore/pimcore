---
title: Installers
description: Bundle installation, migrations, and SettingsStoreAwareInstaller.
---

# Installers

Bundles may need installation tasks beyond being enabled:

- Creating database tables
- Creating or updating class definitions
- Importing translations
- Updating database tables or definitions after a version upgrade

Pimcore defines a basic
[`InstallerInterface`](https://github.com/pimcore/pimcore/blob/2026.x/lib/Extension/Bundle/Installer/InstallerInterface.php),
implemented in
[`AbstractInstaller`](https://github.com/pimcore/pimcore/blob/2026.x/lib/Extension/Bundle/Installer/AbstractInstaller.php).
Commands like `pimcore:bundle:install` trigger the methods on your installer.

Return an installer instance from `getInstaller()` in your bundle class. Return `null`
if your bundle has no installation routines - `pimcore:bundle:install` then skips
installer actions for that bundle.

Define the installer as a service and fetch it from the container on demand:

```yaml
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
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }
}
```

## Migrations

Evolving bundles need to update existing data structures while also supporting fresh
installs. Pimcore integrates the
[Doctrine Migrations Bundle](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
for versioned schema changes.

### Pimcore Specifics

Pimcore adds a `--prefix=` option to Doctrine migration commands, filtering migrations
by namespace. This lets you run only core migrations or only migrations for a specific bundle.

To ensure migration commands only execute migrations from installed Pimcore bundles,
extend `Pimcore\Migrations\BundleAwareMigration` and implement the `getBundleName` method.
This abstract class checks whether the bundle is installed and skips the migration if not.

#### Console Examples

```bash
# run only Pimcore core migrations
bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle

# list migrations for the CMF bundle
bin/console doctrine:migrations:list --prefix=CustomerManagementFrameworkBundle\\Migrations

# run all migrations
bin/console doctrine:migrations:migrate
```

#### Config Examples (`config.yaml`)

```yaml
doctrine_migrations:
    migrations_paths:
        'Pimcore\Bundle\DataHubBundle\Migrations': '@PimcoreDataHubBundle/Migrations'
        'CustomerManagementFrameworkBundle\Migrations': '@PimcoreCustomerManagementFrameworkBundle/Migrations'
```


## SettingsStore Installer

`SettingsStoreAwareInstaller` extends `AbstractInstaller` with:

- Installation state tracking via [Settings Store](../../../09_Development_Tools/07_Settings_Store.md)
  (instead of checking executed migrations)
- Optional marking of migrations as migrated during install
- Migration state reset during uninstall

### Implementation

Extend `SettingsStoreAwareInstaller` and implement the `install` and `uninstall` methods.
At the end of each method, call the corresponding parent method or call
`$this->markInstalled()` / `$this->markUninstalled()` to update the SettingsStore.

To mark migrations up to a certain version as migrated during install (without executing
them), implement `getLastMigrationVersionClassName()` returning the fully qualified class
name of the last migration to mark. This is useful when the install routine already
performs the same work the migrations would do.

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

    public function install(): void
    {
        //do your install stuff   

        $this->markInstalled();
        //or call parent::install();     
    }

    public function uninstall(): void
    {
        //do your uninstall stuff

        $this->markUninstalled();
        //or call parent::uninstall();   
    }
}
```

```yaml
    Pimcore\Bundle\DummyBundle\Installer:
        public: true
        arguments:
            $bundle: "@=service('kernel').getBundle('PimcoreDummyBundle')"
```

### Installation

During bundle installation:
1. The `install` method executes.
2. The bundle is marked as installed in the SettingsStore.
3. If configured, defined migrations are marked as migrated (without executing them).

### Uninstallation

During bundle uninstallation:
1. The `uninstall` method executes.
2. The bundle is marked as uninstalled in the SettingsStore.
3. Migration state for all previously migrated bundle migrations resets
   (without executing them).

### Migrations

Working with migrations follows the same process described in the Migrations section above.

## Further Reading

- [Migrations](../../../09_Development_Tools/08_Migrations.md)
- [Doctrine Migrations](https://www.doctrine-project.org/projects/migrations.html)
- [Doctrine Migrations Bundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
