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
interface can be found in [InstallerInterface](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Extension/Bundle/Installer/InstallerInterface.php) which
is implemented in [AbstractInstaller](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Extension/Bundle/Installer/AbstractInstaller.php) which you can take as starting point.

A pimcore bundle is expected to return an installer instance in `getInstaller()`. This method can also return `null` if you
don't need any installation functionality. In this case, actions which would be handled by an installer will not be available
in the extension manager.

It's recommended to define the installer as service and to fetch it from the container from your bundle class on demand.  
As example:

```yml
services:
    AppBundle\Installer:
        public: true
```

```php
<?php

namespace AppBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AppBundle extends AbstractPimcoreBundle
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
[Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html) library which
provides a powerful migration framework. Building on Doctrine Migrations, Pimcore adds the following features:

* There can be multiple, independent migration sets. A migration set can be seen as isolated set of migrations which can
  be executed in order. Each bundle has its own migration set, concerning only its data structures.
* Doctrine Migrations is focused on database changes. Pimcore adds functionality to be able to use the migrations for generic 
  changes (e.g. changing class definitions).
* An installer can define a specialized install version, which is independent of the remaining migrations. This migration
  is executed on bundle install and reverted on bundle uninstall.
  
To use the migrations for your bundle, you can extend the [MigrationInstaller](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Extension/Bundle/Installer/MigrationInstaller.php)
in your own installer class to make your bundle installer handle migrations. This installer implements the [MigrationInstallerInterface](https://github.com/pimcore/pimcore/blob/898f53756004b2d0e0fdd4079e420b71fd2f2481/pimcore/lib/Pimcore/Extension/Bundle/Installer/MigrationInstallerInterface.php)
which adds support for migrations to your installer.

```yaml
services:
    # note the autowiring - the migration installer has a couple of other dependencies
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    # The migration installer needs the bundle it is operating on upon construction to be able to build its migration configuration.
    # As bundles can't be directly used as service argument, we need to make use of the expression language to fetch the bundle
    # from the kernel upon construction.
    AppBundle\Installer:
        public: true
        arguments:    
            # fetch the bundle via expression language
            $bundle: "@=service('kernel').getBundle('AppBundle')"
```

```php
<?php

namespace AppBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;

class Installer extends MigrationInstaller
{
    public function migrateInstall(Schema $schema, Version $version)
    {
        $table = $schema->createTable('my_bundle');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);
        
        // or
        // $version->addSql('CREATE TABLE my_bundle ...');
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
        $schema->dropTable('my_bundle');
        
        // or
        // $version->addSql('DROP TABLE my_bundle');
    }
}
```

As you can see, you only need to implement the methods `migrateInstall` and `migrateUninstall` which are executed on installation
and uninstallation of your bundle. This is executed as the specialized install version mentioned above, which has the fixed
version `00000001`. After installation your bundle will be migrated to version `00000001`, on uninstallation it will migrate
down to `0`, thus reverting the install migration and calling the `migrateUninstall` method.

The methods receive a [Schema](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html)
object which can be used to modify the database in an object oriented way and a `Version` object which contains metadata
on the current migration and provides an `addSql()` method to register raw SQL queries which should be executed instead 
of the schema changes. Please see the Docrine Migrations documentation for details (the version on a normal migration is
available as `$this->version` and a normal migration class provides an `addSql` method which in turn is delegated to the
version);

In addition to those methods, a couple of before/after methods are available to execute logic before or after install/uninstall
migrations. In addition, `executeMigration` and `migrateToVersion` methods can be used to specifically execute a certain
migration.

As our bundle is now ready to be installed, we can execute the installation routine (this can also be done from the extension
manager UI):

```bash
$ bin/console pimcore:bundle:install AppBundle
Installing bundle AppBundle

Migrating up to 00000001 from 0

  ++ migrating 00000001

     -> CREATE TABLE my_bundle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB

  ++ migrated (0.74s)

  ------------------------

  ++ finished in 0.74s
  ++ 1 migrations executed
  ++ 1 sql queries


 [OK] Bundle "AppBundle" was successfully installed
```

The installer migrated to the special version `00000001` and applied our database changes.


### Writing migrations

As you can see in the example above, the schema above defines the initial database schema for our bundle. Now assume we
need an additional column on our database. We want to create this column on every instance which either installs the bundle
the first time or which updated an already installed bundle. On installation, the `MigrationInstaller` will internally call
the `update` method after installation which makes sure that all unmigrated migrations are migrated. Already existing instances
can directly use `update` to apply unmigrated migrations. 

Start by generating a migration for your bundle:

```bash
$ bin/console pimcore:migrations:generate -b AppBundle
Generated new migration class to "src/AppBundle/Migrations/Version20170822151849.php"
```

This migration class defines an `up` and `down` method which are executed when the migration is executed/reverted. You can 
use the same `schema` object as above or add raw SQL queries:

```php
<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20170822151849 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('my_bundle');
        $table->addColumn('test', 'text');
        
        // or
        // $this->addSql('ALTER TABLE my_bundle ...');
    }

    public function down(Schema $schema)
    {
        $table = $schema->getTable('my_bundle');
        $table->dropColumn('test');
    }
}
```

After creating the migration, you can either do a fresh install of your bundle or update the already installed bundle:

```bash
# this could also done via extension manager UI
$ bin/console pimcore:bundle:update AppBundle
Migrating up to 20170822151849 from 00000001

  ++ migrating 20170822151849

     -> ALTER TABLE my_bundle ADD test LONGTEXT NOT NULL

  ++ migrated (0.68s)

  ------------------------

  ++ finished in 0.68s
  ++ 1 migrations executed
  ++ 1 sql queries


 [OK] Bundle "AppBundle" was successfully updated

```

### Keeping the install schema up to date

While developing your application, you might generate numerous migrations. Instead of applying every single migration on
every fresh install, you can also configure the installer to mark a given version as migrated without actually executing
the migration classes. This gives you the advantage to define the whole schema in the same migration and to have a complete
overview what has to be installed. You just need to make sure to apply the same changes instances which are updates. To mark
a specific version, implement the `getMigrationVersion()` method. This version and all lower versions will be marked as
migrated and won't be executed on an update.
 
Starting from the example above, we directly update our install schema with the changes we want to install define a migration
version to mark:

```php
<?php

namespace AppBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;

class Installer extends MigrationInstaller
{
    public function getMigrationVersion(): string
    {
        return '20170822151849';
    }   
    
    public function migrateInstall(Schema $schema, Version $version)
    {
        $table = $schema->createTable('my_bundle');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('name', 'string');
        
        // this was added
        $table->addColumn('test', 'text');
        
        $table->setPrimaryKey(['id']);  
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
        $schema->dropTable('my_bundle');
    }
}
```

Now, let's see what happens on a fresh install:

```bash
$ bin/console pimcore:bundle:install AppBundle
Installing bundle AppBundle

Migrating up to 00000001 from 0

  ++ migrating 00000001

     -> CREATE TABLE my_bundle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, test LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB

  ++ migrated (0.57s)

  ------------------------

  ++ finished in 0.57s
  ++ 1 migrations executed
  ++ 1 sql queries
  -- Marking version 20170822151849 as migrated


 [OK] Bundle "AppBundle" was successfully installed
 
```

As you can see, the initial schema directly contains the `test` column and `20170822151849` is marked as migrated without
actually being executed. In contrast, this is what happens on an update when updating the bundle from an earlier version:

```bash
$ bin/console pimcore:bundle:update AppBundle
Migrating up to 20170822151849 from 00000001

  ++ migrating 20170822151849

     -> ALTER TABLE my_bundle ADD test LONGTEXT NOT NULL

  ++ migrated (0.7s)

  ------------------------

  ++ finished in 0.7s
  ++ 1 migrations executed
  ++ 1 sql queries


 [OK] Bundle "AppBundle" was successfully updated

```

This does what we expect, but now we have duplicate code in installation migration and our migration class. Depending on
your use case you could also tell the installer to manually execute your migration after installation. In this example it
basically does the same as not defining a version to mark, but you could use this to execute only a handful of needed migration
(e.g. ones with complex logic):


```php
<?php

namespace AppBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;

class Installer extends MigrationInstaller
{
    public function getMigrationVersion(): string
    {
        return '20170822151849';
    }

    public function migrateInstall(Schema $schema, Version $version)
    {
        $table = $schema->createTable('my_bundle');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('name', 'string');

        $table->setPrimaryKey(['id']);
    }

    protected function afterInstallMigration()
    {
        // manually define migrations to run after installation
        $this->executeMigration('20170822151849');
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
        $schema->dropTable('my_bundle');
    }
}
```

As you can see in the output, the defined migration is executed:

```bash
$ bin/console pimcore:bundle:install AppBundle
Installing bundle AppBundle

Migrating up to 00000001 from 0

  ++ migrating 00000001

     -> CREATE TABLE my_bundle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB

  ++ migrated (0.69s)

  ------------------------

  ++ finished in 0.69s
  ++ 1 migrations executed
  ++ 1 sql queries

  ++ migrating 20170822151849

     -> ALTER TABLE my_bundle ADD test LONGTEXT NOT NULL

  ++ migrated (0.49s)


 [OK] Bundle "AppBundle" was successfully installed
```

### Uninstallations

The `MigrationInstaller` by default does **NOT** revert any migrations besides the install migration on uninstallation. As
it is bundle specific what kind of data/structures need to be removed on uninstall, it's completely up to you what you want
to do on uninstall.  

The default logic is:

* Migrate the install migration down - calls `migrateUninstall()`
* Clear any migration states regarding the bundle in the `pimcore_migrations` table

From this point on the bundle would execute all migrations on a fresh installation. As this may run into errors due to duplicate
tables, it is recommended to make your migrations as failsafe as possible (e.g. check if a table exists before creating it).

As in the examples above, you can directly execute migrations on uninstallation:

```php
<?php

namespace AppBundle;

use Pimcore\Extension\Bundle\Installer\MigrationInstaller;

class Installer extends MigrationInstaller
{
    // [...]
    
    protected function beforeUninstallMigration()
    {
        $this->migrateToVersion('0');
        $this->outputWriter->write(PHP_EOL);

        // or manually revert a single migration - the second parameter defines the migration as being migrated down
        // $this->executeMigration('20170822151849', false);
    }
}
```


### Non-DB Changes

Despite being a DB-oriented approach, migrations can be used to alter other structural elements as class definitions. Inside
migrations, you can make full use of Pimcore's APIs, for example to alter or import a class definition. As an example, we'll
alter the description field in a `blogArticle` class:

```php
<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Object\ClassDefinition;

class Version20170822160703 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription('[MIGRATIONS] ' . $classDefinition->getDescription() ?? '');
        $classDefinition->save();
    }

    public function down(Schema $schema)
    {
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription(preg_replace('/^\[MIGRATIONS\] /', '', $classDefinition->getDescription()));
        $classDefinition->save();
    }
}
```

This migration can be executed as every other migration by excuting `update`:

```bash
$ bin/console pimcore:bundle:update AppBundle
Migrating up to 20170822160703 from 20170822151849

  ++ migrating 20170822160703

Migration 20170822160703 was executed but did not result in any SQL statements.

  ++ migrated (0.37s)

  ------------------------

  ++ finished in 0.37s
  ++ 1 migrations executed
  ++ 0 sql queries


 [OK] Bundle "AppBundle" was successfully updated

```

As you can see, the change was successfully applied, but yielded a warning about SQL statements not being applied. As our
changes are not executed through the `schema` object or the `addSql` method, the migrations library does not know what has
been applied triggers the warning. To avoid this, the `AbstractPimcoreMigration` defines the following method, which can
be used to suppress the warning if it returns `false`:

```php
<?php

namespace AppBundle\Migrations;

use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20170822160703 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    // [...]
}
```

```bash
 bin/console pimcore:bundle:update AppBundle
Migrating up to 20170822160703 from 20170822151849

  ++ migrating 20170822160703


  ++ migrated (0.35s)

  ------------------------

  ++ finished in 0.35s
  ++ 1 migrations executed
  ++ 0 sql queries


 [OK] Bundle "AppBundle" was successfully updated

```

### Handling `--dry-run` in non-DB migrations

Doctrine Migrations can be executed in a `dry-run` mode which does not actually change data. For plain SQL migrations this
is quite easy as all SQL queries are collected before being executed. In `dry-run` mode those collected queries are simply
not executed. For migrations not only handling DB changes, this is more complicated. By default, migrations do not know
about the `dry-run` state, but the `AbstractPimcoreMigration` implements the `DryRunMigrationInterface` which adds the 
`dry-run` context on the migration itself:

```php
<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Object\ClassDefinition;

class Version20170822160703 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        // dryRunMessage will prefix the message with "DRY-RUN:" in dry run mode
        $this->writeMessage($this->dryRunMessage('Adding description to blogArticle class'));
        
        if ($this->isDryRun()) {
            return;
        }
        
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription('[MIGRATIONS] ' . $classDefinition->getDescription() ?? '');
        $classDefinition->save();            
    }

    public function down(Schema $schema)
    {
        $this->writeMessage($this->dryRunMessage('Removing description from blogArticle class'));
        
        if ($this->isDryRun()) {
            return;
        }
        
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription(preg_replace('/^\[MIGRATIONS\] /', '', $classDefinition->getDescription()));
        $classDefinition->save();
    }
}
```

### Interacting with migrations directly

If needed, you can directly interact with the migrations library (actually, we already did that above when generating a
new migration) to directly execute a migration or to get informations on the current migration state. You can find the CLI
commands in the `pimcore:migrations` namespace. As example:

```bash
$ $ bin/console pimcore:migrations:status -b AppBundle
  
   == Configuration
  
      >> Name:                                               AppBundle Migrations
      >> Database Driver:                                    pdo_mysql
      >> Database Name:                                      pimcore5
      >> Configuration Source:                               manually configured
      >> Version Table Name:                                 pimcore_migrations
      >> Version Column Name:                                version
      >> Migrations Namespace:                               AppBundle\Migrations
      >> Migrations Directory:                               src/AppBundle/Migrations
      >> Previous Version:                                   2017-08-22 15:18:49 (20170822151849)
      >> Current Version:                                    2017-08-22 16:07:03 (20170822160703)
      >> Next Version:                                       Already at latest version
      >> Latest Version:                                     2017-08-22 16:07:03 (20170822160703)
      >> Executed Migrations:                                3
      >> Executed Unavailable Migrations:                    1
      >> Available Migrations:                               2
      >> New Migrations:                                     0
```

By adding the `-b` option, you configure the migrations commands to use a bundle configuration. Alternatively, you can also
use the global migration set which is not bundle specific by omitting the `-b` option. This gives you the possibility to 
define application wide migrations which are not bound to an installer. To execute those migrations, please directly use
the `pimcore:migrations:migrate` command instead of `pimcore:bundle:update`.

For further details please see

* [Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html)
* [Doctrine Migrations Bundle](http://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
